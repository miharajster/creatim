import Cookies from 'js-cookie';
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export async function initializeSession() {
  const sessionId = Cookies.get('SessionId');
  const pwd = Cookies.get('pwd');
  
  // If both exist, validate with backend
  if (sessionId && pwd) {
    try {
      await validateSession(sessionId, pwd);
      // Session is valid, load cart from backend
      return { sessionId, pwd };
    } catch (error) {
      console.error('Session validation failed:', error);
      // Continue to create new session
    }
  }
  
  // Create new session if doesn't exist or validation failed
  return await createNewSession();
}

export async function validateSession(sessionId, pwd) {
  const response = await axios.get(`${API_BASE_URL}update-cart.php`, {
    params: { session_id: sessionId, pwd }
  });
  return response.data;
}

export async function updateCartOnServer(sessionId, pwd, cartData, phone = null) {
  try {
    const payload = {
      session_id: sessionId,
      pwd: pwd,
      cart: cartData
    };
    
    if (phone !== null) {
      payload.phone = phone;
    }
    
    const response = await axios.post(`${API_BASE_URL}update-cart.php`, payload);
    return response.data;
  } catch (error) {
    // Check for 404 error
    if (error.response && error.response.status === 404) {
      alert('Error in api configuration');
      console.error('API endpoint not found (404):', error);
    } else {
      console.error('Failed to update cart on server:', error);
    }
    throw error;
  }
}

export async function loadCartFromServer(sessionId, pwd) {
  try {
    const response = await axios.get(`${API_BASE_URL}update-cart.php`, {
      params: { session_id: sessionId, pwd }
    });
    if (response.data.success) {
      const cartString = response.data.data.cart;
      const submitted = response.data.data.submitted;
      const phone = response.data.data.phone;
      const cartData = cartString ? JSON.parse(cartString) : [];
      
      return {
        cart: cartData,
        submitted: submitted === 1,
        phone: phone || null
      };
    } else {
      // success: false, session invalid
      console.log('Session invalid, creating new session...');
      throw new Error('Invalid session');
    }
  } catch (error) {
    // Check for 404 error
    if (error.response && error.response.status === 404) {
      alert('Error in api configuration');
      console.error('API endpoint not found (404):', error);
    } else {
      console.error('Failed to load cart from server:', error);
    }
    throw error; // Re-throw to be caught by main.js
  }
}

export async function createNewSession() {
  try {
    const response = await axios.get(`${API_BASE_URL}new-session.php`);
    if (response.data.success) {
      const { session_id, pwd: newPwd } = response.data.data;
      Cookies.set('SessionId', session_id, { expires: 365 });
      Cookies.set('pwd', newPwd, { expires: 365 });
      return { sessionId: session_id, pwd: newPwd };
    }
  } catch (error) {
    // Check for 404 error
    if (error.response && error.response.status === 404) {
      alert('Error in api configuration');
      console.error('API endpoint not found (404):', error);
    } else {
      console.error('Failed to create new session:', error);
    }
  }
  return null;
}
