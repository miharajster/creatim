import { createStore } from 'vuex';
import { updateCartOnServer } from '../utils/session';
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export default createStore({
  state: {
    cart: {
      articles: [], // [{ id: 1, amount: 1 }]
      subscriptions: [] // [{ id: 3, amount: 1 }]
    },
    sessionId: null,
    pwd: null,
    phone: null,
    purchases: {
      articles: [], // [{ id: 1, amount: 2 }]
      subscriptions: [] // [{ id: 3 }]
    }
  },
  
  getters: {
    cartItemsCount: (state) => {
      // Sum amounts from both articles and subscriptions
      const articlesCount = state.cart.articles.reduce((total, item) => total + item.amount, 0);
      const subscriptionsCount = state.cart.subscriptions.reduce((total, item) => total + item.amount, 0);
      return articlesCount + subscriptionsCount;
    },
    cartArticles: (state) => {
      return state.cart.articles;
    },
    cartSubscriptions: (state) => {
      return state.cart.subscriptions;
    },
    allCartItems: (state) => {
      // Combined array for backward compatibility
      return [...state.cart.articles, ...state.cart.subscriptions];
    },
    phone: (state) => {
      return state.phone;
    },
    isArticleInCart: (state) => (articleId) => {
      return state.cart.articles.some(item => item.id === articleId);
    },
    isSubscriptionInCart: (state) => (subscriptionId) => {
      return state.cart.subscriptions.some(item => item.id == subscriptionId); // Use == for type coercion
    },
    isArticlePurchased: (state) => (articleId) => {
      return state.purchases.articles.some(article => article.id === articleId);
    },
    isSubscriptionPurchased: (state) => (subscriptionId) => {
      return state.purchases.subscriptions.some(subscription => subscription.id === subscriptionId);
    },
    purchases: (state) => {
      return state.purchases;
    }
  },
  
  mutations: {
    SET_SESSION(state, { sessionId, pwd }) {
      state.sessionId = sessionId;
      state.pwd = pwd;
    },
    
    SET_PHONE(state, phone) {
      state.phone = phone;
    },
    
    SET_PURCHASES(state, purchases) {
      state.purchases = purchases;
    },
    
    ADD_ARTICLE_TO_CART(state, articleId) {
      const existingItem = state.cart.articles.find(item => item.id === articleId);
      if (!existingItem) {
        state.cart.articles.push({ id: articleId, amount: 1 });
      }
    },
    
    ADD_SUBSCRIPTION_TO_CART(state, subscriptionId) {
      // Only allow one subscription - replace any existing subscription
      state.cart.subscriptions = [{ id: subscriptionId, amount: 1 }];
    },
    
    REMOVE_ARTICLE_FROM_CART(state, articleId) {
      const index = state.cart.articles.findIndex(item => item.id === articleId);
      if (index > -1) {
        state.cart.articles.splice(index, 1);
      }
    },
    
    REMOVE_SUBSCRIPTION_FROM_CART(state, subscriptionId) {
      const index = state.cart.subscriptions.findIndex(item => item.id === subscriptionId);
      if (index > -1) {
        state.cart.subscriptions.splice(index, 1);
      }
    },
    
    LOAD_CART(state, { articles, subscriptions }) {
      state.cart.articles = articles || [];
      state.cart.subscriptions = subscriptions || [];
    },
    
    CLEAR_CART(state) {
      state.cart.articles = [];
      state.cart.subscriptions = [];
    }
  },
  
  actions: {
    setSession({ commit }, credentials) {
      commit('SET_SESSION', credentials);
    },
    
    async syncCartToServer({ state, commit, dispatch }) {
      if (state.sessionId && state.pwd) {
        try {
          // Combine articles and subscriptions into single array for backend
          const combinedCart = [...state.cart.articles, ...state.cart.subscriptions];
          await updateCartOnServer(state.sessionId, state.pwd, combinedCart, state.phone);
        } catch (error) {
          console.error('Failed to sync cart to server:', error);
          // Backend will auto-reset submitted carts, so no special handling needed
        }
      }
    },
    
    async updatePhone({ commit, dispatch }, phone) {
      commit('SET_PHONE', phone);
      await dispatch('syncCartToServer');
    },
    
    async addArticleToCart({ commit, dispatch }, articleId) {
      commit('ADD_ARTICLE_TO_CART', articleId);
      await dispatch('syncCartToServer');
    },
    
    async addSubscriptionToCart({ commit, dispatch }, subscriptionId) {
      commit('ADD_SUBSCRIPTION_TO_CART', subscriptionId);
      await dispatch('syncCartToServer');
    },
    
    async removeArticleFromCart({ commit, dispatch }, articleId) {
      commit('REMOVE_ARTICLE_FROM_CART', articleId);
      await dispatch('syncCartToServer');
    },
    
    async removeSubscriptionFromCart({ commit, dispatch }, subscriptionId) {
      commit('REMOVE_SUBSCRIPTION_FROM_CART', subscriptionId);
      await dispatch('syncCartToServer');
    },
    
    async loadCartFromServer({ commit }, cartData) {
      if (!cartData || cartData.length === 0) {
        commit('LOAD_CART', { articles: [], subscriptions: [] });
        return;
      }

      const articles = [];
      const subscriptions = [];

      // Identify each item by fetching from API
      for (const item of cartData) {
        try {
          // Try articles first
          const articleResponse = await axios.get(`${API_BASE_URL}articles.php?id=${item.id}`);
          if (articleResponse.data.success) {
            articles.push(item);
            continue;
          }
        } catch (e) {
          // Not an article, try subscription
        }

        try {
          // Try subscriptions
          const subResponse = await axios.get(`${API_BASE_URL}subscriptions.php?id=${item.id}`);
          if (subResponse.data.success) {
            subscriptions.push(item);
          }
        } catch (e) {
          console.error('Item not found in articles or subscriptions:', item.id);
        }
      }

      commit('LOAD_CART', { articles, subscriptions });
    },
    
    async clearCart({ commit, dispatch }) {
      commit('CLEAR_CART');
      await dispatch('syncCartToServer');
    },
    
    async fetchPurchases({ commit, state }) {
      if (!state.sessionId || !state.pwd) {
        return;
      }
      
      try {
        // Only send session_id and pwd (no phone required)
        const params = {
          session_id: state.sessionId,
          pwd: state.pwd
        };
        
        const response = await axios.get(`${API_BASE_URL}purchases.php`, { params });
        
        if (response.data.success) {
          commit('SET_PURCHASES', response.data.data);
        }
      } catch (error) {
        // Check for 404 error
        if (error.response && error.response.status === 404) {
          alert('Error in api configuration');
          console.error('API endpoint not found (404):', error);
          return;
        }
        console.error('Failed to fetch purchases:', error);
      }
    }
  }
});
