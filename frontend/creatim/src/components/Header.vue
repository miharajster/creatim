<template>
  <header class="c-header">
    <div class="c-header__container">
      <router-link to="/" class="c-header__logo">
        Creatim
      </router-link>
      <div class="c-header__right">
        <div v-if="phone" class="c-header__phone">
          <i class="fa fa-phone"></i>
          <span class="c-header__phone-display">{{ phone }}</span>
        </div>
        <router-link to="/cart" class="c-header__cart-btn">
          <i class="fa fa-shopping-cart"></i>
          Cart
          <span v-if="cartCount > 0" class="c-header__badge">{{ cartCount }}</span>
        </router-link>
      </div>
    </div>
  </header>
</template>

<script>
import { computed } from 'vue';
import { useStore } from 'vuex';

export default {
  name: 'Header',
  setup() {
    const store = useStore();
    
    const cartCount = computed(() => store.getters.cartItemsCount);
    const phone = computed(() => store.getters.phone);
    
    return {
      cartCount,
      phone
    };
  }
};
</script>

<style scoped>
.c-header__right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.c-header__phone {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background-color: #f5f5f5;
  border-radius: 8px;
}

.c-header__phone i {
  color: #42b883;
  font-size: 14px;
}

.c-header__phone-display {
  font-size: 14px;
  color: #35495e;
  font-weight: 500;
}
</style>
