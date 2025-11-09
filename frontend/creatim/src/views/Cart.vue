<template>
  <div>
    <Header />
    
    <section class="c-section">
      <div class="o-container">
        <div class="c-section__header">
          <h2 class="c-section__title">Shopping Cart</h2>
          <p class="c-section__subtitle">Review your items</p>
        </div>
        
        <div v-if="loading" class="u-text-center u-p-lg">
          <i class="fa fa-spinner fa-spin" style="font-size: 48px; color: #42b883;"></i>
          <p style="margin-top: 1rem; color: #666;">Loading cart...</p>
        </div>
        
        <div v-else-if="cartArticles.length === 0 && cartSubscriptions.length === 0" class="u-text-center u-p-lg">
          <p style="font-size: 18px; color: #666;">Your cart is empty</p>
          <router-link to="/" style="color: #42b883; margin-top: 1rem; display: inline-block;">
            <i class="fa fa-arrow-left"></i> Continue Shopping
          </router-link>
        </div>
        
        <div v-else>
          <div class="cart-items">
            <div v-for="item in cartItemsWithData" :key="item.id" class="cart-item">
              <div class="cart-item__info">
                <h3 class="cart-item__title">{{ item.title }}</h3>
                <p class="cart-item__type">{{ item.type }}</p>
                <div class="cart-item__pricing">
                  <span class="cart-item__unit-price">€{{ formatPrice(item.price) }}</span>
                </div>
                <div class="cart-item__actions">
                  <button @click="removeItem(item.id)" class="cart-item__remove">
                    <i class="fa fa-trash"></i> Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="phone-form">
            <h3 class="phone-form__title">Contact Information</h3>
            <form @submit.prevent="submitPhone" class="phone-form__container">
              <div class="phone-form__input-group">
                <i class="fa fa-phone"></i>
                <input 
                  type="tel" 
                  v-model="phoneInput" 
                  placeholder="Enter phone number"
                  class="phone-form__input"
                  pattern="[0-9]*"
                  inputmode="numeric"
                />
                <button type="submit" class="phone-form__submit" :disabled="isSubmittingPhone">
                  <i :class="isSubmittingPhone ? 'fa fa-spinner fa-spin' : 'fa fa-save'"></i>
                  {{ isSubmittingPhone ? 'Saving...' : 'Save Phone' }}
                </button>
              </div>
              <p v-if="phoneMessage" :class="['phone-form__message', phoneMessageType]">
                {{ phoneMessage }}
              </p>
            </form>
          </div>
          
          <div class="cart-total">
            <div class="cart-total__row">
              <span class="cart-total__label">Total Items:</span>
              <span class="cart-total__value">{{ totalItems }}</span>
            </div>
            <div class="cart-total__row cart-total__grand">
              <span class="cart-total__label">Total Price:</span>
              <span class="cart-total__value">€{{ formatPrice(totalPrice) }}</span>
            </div>
          </div>
          
          <div class="cart-actions">
            <button @click="clearCart" class="cart-actions__clear">
              <i class="fa fa-trash"></i> Clear Cart
            </button>
            <button @click="checkout" class="cart-actions__checkout" :disabled="!canCheckout">
              <i :class="isCheckingOut ? 'fa fa-spinner fa-spin' : 'fa fa-credit-card'"></i> 
              {{ isCheckingOut ? 'Processing...' : `Checkout - €${formatPrice(totalPrice)}` }}
            </button>
          </div>
          <p v-if="!isPhoneValid && allCartItems.length > 0" class="checkout-warning">
            <i class="fa fa-exclamation-triangle"></i> Please enter a valid phone number to checkout
          </p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue';
import { useStore } from 'vuex';
import axios from 'axios';
import Header from '../components/Header.vue';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export default {
  name: 'Cart',
  components: {
    Header
  },
  setup() {
    const store = useStore();
    const loading = ref(false);
    const isCheckingOut = ref(false);
    const isSubmittingPhone = ref(false);
    const phoneInput = ref('');
    const phoneMessage = ref('');
    const phoneMessageType = ref('');
    const cartItemsWithData = ref([]);
    
    const cartArticles = computed(() => store.getters.cartArticles);
    const cartSubscriptions = computed(() => store.getters.cartSubscriptions);
    const allCartItems = computed(() => store.getters.allCartItems);
    const totalItems = computed(() => store.getters.cartItemsCount);
    const phone = computed(() => store.getters.phone);
    
    const totalPrice = computed(() => {
      return cartItemsWithData.value.reduce((total, item) => {
        return total + (item.price * item.amount);
      }, 0);
    });
    
    const isPhoneValid = computed(() => {
      if (!phone.value) return false;
      const phoneStr = phone.value.toString();
      return /^\d+$/.test(phoneStr);
    });
    
    const canCheckout = computed(() => {
      return isPhoneValid.value && !isCheckingOut.value;
    });
    
    const formatPrice = (priceInCents) => {
      return (priceInCents / 100).toFixed(2);
    };
    
    const fetchItemData = async (itemId) => {
      // Try to fetch as article first
      try {
        const response = await axios.get(`${API_BASE_URL}articles.php?id=${itemId}`);
        if (response.data.success) {
          const article = response.data.data;
          return {
            id: article.id,
            title: article.name,
            price: article.price,
            type: 'Article'
          };
        }
      } catch (error) {
        // Not an article, try subscription
      }
      
      // Try to fetch as subscription
      try {
        const response = await axios.get(`${API_BASE_URL}subscriptions.php?id=${itemId}`);
        if (response.data.success) {
          const subscription = response.data.data;
          return {
            id: subscription.id,
            title: subscription.description,
            price: subscription.price,
            type: subscription.physical ? 'Physical Subscription' : 'Digital Subscription'
          };
        }
      } catch (error) {
        console.error(`Failed to fetch item ${itemId}:`, error);
      }
      
      // If neither worked, return placeholder
      return {
        id: itemId,
        title: `Item #${itemId} (not found)`,
        price: 0,
        type: 'Unknown'
      };
    };
    
    const loadCartData = async () => {
      loading.value = true;
      const itemsData = [];
      
      for (const cartItem of allCartItems.value) {
        const itemData = await fetchItemData(cartItem.id);
        itemsData.push({
          ...itemData,
          amount: cartItem.amount
        });
      }
      
      cartItemsWithData.value = itemsData;
      loading.value = false;
    };
    
    const removeItem = (itemId) => {
      if (confirm('Remove this item from cart?')) {
        store.dispatch('removeFromCart', itemId);
      }
    };
    
    const clearCart = () => {
      if (confirm('Are you sure you want to clear your cart?')) {
        store.dispatch('clearCart');
        cartItemsWithData.value = [];
      }
    };
    
    const checkout = async () => {
      if (allCartItems.value.length === 0) {
        alert('Your cart is empty!');
        return;
      }
      
      isCheckingOut.value = true;
      
      try {
        const sessionId = store.state.sessionId;
        const pwd = store.state.pwd;
        
        if (!sessionId || !pwd) {
          alert('Error in processing order, try later');
          isCheckingOut.value = false;
          return;
        }
        
        const response = await axios.post(`${API_BASE_URL}order.php`, {
          session_id: sessionId,
          pwd: pwd
        });
        
        if (response.data.success) {
          alert('Ordered successfully');
          
          // Clear local cart state (don't sync to server as cart is already marked submitted)
          store.commit('CLEAR_CART');
          cartItemsWithData.value = [];
          
          // Fetch cart from server - backend has marked it as submitted
          // This will trigger automatic new session creation in main.js on next load
          // or when user tries to add items (via 403 error handling in store)
        } else {
          alert('Error in processing order, try later');
        }
      } catch (error) {
        console.error('Checkout error:', error);
        alert('Error in processing order, try later');
      } finally {
        isCheckingOut.value = false;
      }
    };
    
    const submitPhone = async () => {
      isSubmittingPhone.value = true;
      phoneMessage.value = '';
      
      try {
        // Clean phone input (digits only)
        const cleanPhone = phoneInput.value.replace(/\D/g, '');
        
        if (!cleanPhone) {
          phoneMessage.value = 'Please enter a phone number';
          phoneMessageType.value = 'error';
          isSubmittingPhone.value = false;
          return;
        }
        
        const sessionId = store.state.sessionId;
        const pwd = store.state.pwd;
        
        if (!sessionId || !pwd) {
          phoneMessage.value = 'Session error. Please refresh the page.';
          phoneMessageType.value = 'error';
          isSubmittingPhone.value = false;
          return;
        }
        
        // Send phone to update-cart API
        const combinedCart = [...store.state.cart.articles, ...store.state.cart.subscriptions];
        await axios.post(`${API_BASE_URL}update-cart.php`, {
          session_id: sessionId,
          pwd: pwd,
          cart: combinedCart,
          phone: cleanPhone
        });
        
        // Refresh state from API
        const response = await axios.get(`${API_BASE_URL}update-cart.php`, {
          params: { session_id: sessionId, pwd: pwd }
        });
        
        if (response.data.success) {
          const updatedPhone = response.data.data.phone;
          store.commit('SET_PHONE', updatedPhone);
          phoneMessage.value = 'Phone number saved successfully!';
          phoneMessageType.value = 'success';
          
          // Clear message after 3 seconds
          setTimeout(() => {
            phoneMessage.value = '';
          }, 3000);
        }
      } catch (error) {
        console.error('Error saving phone:', error);
        phoneMessage.value = error.response?.data?.error || 'Error saving phone number';
        phoneMessageType.value = 'error';
      } finally {
        isSubmittingPhone.value = false;
      }
    };
    
    // Watch for phone changes from store and update input
    watch(phone, (newPhone) => {
      if (newPhone && phoneInput.value !== newPhone.toString()) {
        phoneInput.value = newPhone.toString();
      }
    }, { immediate: true });
    
    // Watch for cart changes and reload data
    watch(allCartItems, () => {
      loadCartData();
    }, { deep: true });
    
    // Load cart data on mount
    onMounted(() => {
      loadCartData();
    });
    
    return {
      loading,
      isCheckingOut,
      isSubmittingPhone,
      phoneInput,
      phoneMessage,
      phoneMessageType,
      cartArticles,
      cartSubscriptions,
      allCartItems,
      cartItemsWithData,
      totalItems,
      totalPrice,
      isPhoneValid,
      canCheckout,
      removeItem,
      clearCart,
      checkout,
      submitPhone,
      formatPrice
    };
  }
};
</script>

<style scoped>
.cart-items {
  margin-bottom: 2rem;
}

.phone-form {
  background: white;
  padding: 2rem;
  margin-bottom: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.phone-form__title {
  margin: 0 0 1rem 0;
  color: #35495e;
  font-size: 20px;
}

.phone-form__container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.phone-form__input-group {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.phone-form__input-group i {
  color: #42b883;
  font-size: 18px;
}

.phone-form__input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.3s ease;
}

.phone-form__input:focus {
  outline: none;
  border-color: #42b883;
}

.phone-form__submit {
  padding: 0.75rem 1.5rem;
  background-color: #42b883;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.phone-form__submit i {
  color: white;
}

.phone-form__submit:hover:not(:disabled) {
  background-color: #35a372;
  transform: translateY(-2px);
}

.phone-form__submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.phone-form__message {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 14px;
}

.phone-form__message.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.phone-form__message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.cart-item {
  background: white;
  padding: 1.5rem;
  margin-bottom: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.cart-item__info {
  width: 100%;
}

.cart-item__title {
  margin: 0 0 0.5rem 0;
  color: #35495e;
  font-size: 20px;
}

.cart-item__type {
  color: #666;
  font-size: 14px;
  margin: 0 0 1rem 0;
}

.cart-item__pricing {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.5rem 0;
  border-top: 1px solid #eee;
  border-bottom: 1px solid #eee;
}

.cart-item__unit-price {
  color: #666;
  font-size: 14px;
}

.cart-item__subtotal {
  font-size: 18px;
  font-weight: bold;
  color: #42b883;
}

.cart-item__actions {
  display: flex;
  align-items: center;
  margin-top: 0.5rem;
}

.cart-item__remove {
  background-color: #ff6b6b;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  height: 32px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cart-item__remove:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

.cart-total {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.cart-total__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #eee;
}

.cart-total__row:last-child {
  border-bottom: none;
}

.cart-total__grand {
  font-size: 20px;
  font-weight: bold;
  color: #35495e;
  padding-top: 1.5rem;
  border-top: 2px solid #42b883;
}

.cart-total__label {
  color: #666;
}

.cart-total__grand .cart-total__label {
  color: #35495e;
}

.cart-total__value {
  font-weight: 600;
  color: #42b883;
}

.cart-total__grand .cart-total__value {
  font-size: 24px;
}

.cart-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.cart-actions__clear,
.cart-actions__checkout {
  flex: 1;
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cart-actions__clear {
  background-color: #ff6b6b;
  color: white;
}

.cart-actions__checkout {
  background-color: #42b883;
  color: white;
}

.cart-actions__clear:hover,
.cart-actions__checkout:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-2px);
}

.cart-actions__checkout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.checkout-warning {
  text-align: center;
  margin-top: 1rem;
  padding: 1rem;
  background-color: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.checkout-warning i {
  font-size: 16px;
}
</style>
