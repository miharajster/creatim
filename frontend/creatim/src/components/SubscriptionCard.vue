<template>
  <div class="c-card">
    <h3 class="c-card__title">{{ subscription.physical ? '📦 Physical' : '💻 Digital' }}</h3>
    <p class="c-card__description">{{ subscription.description }}</p>
    <div class="c-card__price">€{{ formatPrice(subscription.price) }}/month</div>
    <button 
      @click="addToCart" 
      class="c-card__button" 
      :class="{ 'in-cart': isInCart && !isPurchased, 'added': isAdded, 'subscribed': isPurchased }"
      :disabled="isInCart && !isPurchased">
      <i :class="isPurchased ? 'fa fa-star' : (isInCart ? 'fa fa-check-circle' : (isAdded ? 'fa fa-check' : 'fa fa-cart-plus'))"></i>
      {{ isPurchased ? 'Subscribed' : (isInCart ? 'In Cart' : (isAdded ? 'Added!' : 'Add to Cart')) }}
    </button>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';

export default {
  name: 'SubscriptionCard',
  props: {
    subscription: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const store = useStore();
    const isAdded = ref(false);
    
    const isInCart = computed(() => {
      return store.getters.isSubscriptionInCart(props.subscription.id);
    });
    
    const isPurchased = computed(() => {
      return store.getters.isSubscriptionPurchased(props.subscription.id);
    });
    
    const addToCart = () => {
      // Don't add if already purchased
      if (isPurchased.value) return;
      
      // Always allow adding subscription (will replace any existing one)
      store.dispatch('addSubscriptionToCart', props.subscription.id);
      
      // Show feedback
      isAdded.value = true;
      setTimeout(() => {
        isAdded.value = false;
      }, 1500);
    };
    
    const formatPrice = (priceInCents) => {
      return (priceInCents / 100).toFixed(2);
    };
    
    return {
      addToCart,
      formatPrice,
      isAdded,
      isInCart,
      isPurchased
    };
  }
};
</script>

<style scoped>
.c-card__button.added {
  background-color: #28a745;
}

.c-card__button.in-cart {
  background-color: #6c757d;
  cursor: not-allowed;
  opacity: 0.8;
}

.c-card__button.in-cart:hover {
  background-color: #6c757d;
  transform: none;
}

.c-card__button.subscribed {
  background-color: #ff9800;
  cursor: pointer;
  opacity: 1;
}

.c-card__button.subscribed:hover {
  background-color: #fb8c00;
  transform: translateY(-2px);
}
</style>
