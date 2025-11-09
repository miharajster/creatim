<template>
  <div class="c-card">
    <h3 class="c-card__title">{{ article.name }}</h3>
    <p class="c-card__description">{{ article.description }}</p>
    <div class="c-card__price">€{{ formatPrice(article.price) }}</div>
    <button 
      @click="addToCart" 
      class="c-card__button" 
      :class="{ 'in-cart': isInCart, 'added': isAdded, 'purchased': isPurchased }"
      :disabled="isInCart">
      <i :class="isPurchased ? 'fa fa-book-open' : (isInCart ? 'fa fa-check-circle' : (isAdded ? 'fa fa-check' : 'fa fa-cart-plus'))"></i>
      {{ isPurchased ? 'READ' : (isInCart ? 'In Cart' : (isAdded ? 'Added!' : 'Add to Cart')) }}
    </button>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';

export default {
  name: 'ArticleCard',
  props: {
    article: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const store = useStore();
    const isAdded = ref(false);
    
    const isInCart = computed(() => {
      return store.getters.isArticleInCart(props.article.id);
    });
    
    const isPurchased = computed(() => {
      return store.getters.isArticlePurchased(props.article.id);
    });
    
    const addToCart = () => {
      if (isInCart.value || isPurchased.value) return;
      
      store.dispatch('addArticleToCart', props.article.id);
      
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

.c-card__button.purchased {
  background-color: #17a2b8;
  cursor: pointer;
  opacity: 1;
}

.c-card__button.purchased:hover {
  background-color: #138496;
  transform: translateY(-2px);
}
</style>
