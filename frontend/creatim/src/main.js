import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import './styles/main.scss'
import '@fortawesome/fontawesome-free/css/all.css'
import Cookies from 'js-cookie'
import { loadCartFromServer, createNewSession } from './utils/session'

const app = createApp(App)
app.use(router)
app.use(store)

// Initialize session and load cart asynchronously
;(async () => {
  try {
    let session = null
    
    // Read session_id and pwd from cookies first
    const sessionId = Cookies.get('SessionId')
    const pwd = Cookies.get('pwd')
    
    if (sessionId && pwd) {
      // Cookies exist, try to use them
      console.log('Session found in cookies')
      
      try {
        session = { sessionId, pwd }
        
        // Set session in store
        store.dispatch('setSession', session)
        
        // Fetch cart from update-cart.php
        const result = await loadCartFromServer(sessionId, pwd)
        
        // Check if cart is submitted
        if (result.submitted) {
          console.log('Cart was submitted, creating new session...')
          // Cart was submitted, create new session and clear cart
          const newSession = await createNewSession()
          if (newSession) {
            store.dispatch('setSession', newSession)
            store.dispatch('loadCartFromServer', [])
            store.commit('SET_PHONE', null)
            session = newSession
          } else {
            console.error('Failed to create new session after submitted cart')
            session = null
          }
        } else {
          // Load cart data if not submitted
          if (result.cart && result.cart.length > 0) {
            store.dispatch('loadCartFromServer', result.cart)
          }
          // Load phone from server
          if (result.phone) {
            store.commit('SET_PHONE', result.phone)
          }
        }
        
        // Fetch purchases from purchases.php
        await store.dispatch('fetchPurchases')
      } catch (error) {
        // Check for 404 error
        if (error.response && error.response.status === 404) {
          alert('Error in api configuration')
          console.error('API endpoint not found (404):', error)
          return
        }
        
        // Error with existing session, create new one
        console.error('Error loading session from cookies, creating new session:', error)
        session = await createNewSession()
        
        if (session) {
          store.dispatch('setSession', session)
        } else {
          console.error('Failed to create new session')
        }
      }
    } else {
      // No cookies, create new session from new-session.php
      console.log('No session found, creating new session')
      
      try {
        session = await createNewSession()
        
        if (session) {
          // Set new session in store
          store.dispatch('setSession', session)
        } else {
          console.error('Failed to create new session')
        }
      } catch (error) {
        // Check for 404 error
        if (error.response && error.response.status === 404) {
          alert('Error in api configuration')
          console.error('API endpoint not found (404):', error)
          return
        }
        console.error('Error creating new session:', error)
      }
    }
    
    // Set up automatic purchases refresh every 10 seconds (if session exists)
    if (session) {
      setInterval(() => {
        store.dispatch('fetchPurchases')
      }, 10000)
    }
  } catch (error) {
    // Check for 404 error
    if (error.response && error.response.status === 404) {
      alert('Error in api configuration')
      console.error('API endpoint not found (404):', error)
    } else {
      console.error('Failed to initialize session:', error)
    }
  }
})()

app.mount('#app')
