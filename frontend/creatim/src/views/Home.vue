<template>
  <div>
    <Header />
    
    <!-- Error Message -->
    <div v-if="hasError" class="error-message">
      <div class="o-container">
        <div class="error-box">
          <i class="fa fa-exclamation-triangle"></i>
          <h2>API communication error</h2>
          <p>Please contact us on <a href="mailto:info@creatim.com">info@creatim.com</a></p>
        </div>
      </div>
    </div>
    
    <template v-else>
      <!-- Articles Section -->
      <section class="c-section">
        <div class="o-container">
          <div class="c-section__header">
            <h2 class="c-section__title">Articles</h2>
            <p class="c-section__subtitle">Browse our latest articles</p>
          </div>
          <div class="o-grid o-grid--3">
            <ArticleCard 
              v-for="article in articles" 
              :key="article.id" 
              :article="article" 
            />
          </div>
        </div>
      </section>
      
      <!-- Subscriptions Section -->
      <section class="c-section">
        <div class="o-container">
          <div class="c-section__header">
            <h2 class="c-section__title">Subscriptions</h2>
            <p class="c-section__subtitle">Choose your subscription plan</p>
          </div>
          <div class="o-grid o-grid--3">
            <SubscriptionCard 
              v-for="subscription in subscriptions" 
              :key="subscription.id" 
              :subscription="subscription" 
            />
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Header from '../components/Header.vue';
import ArticleCard from '../components/ArticleCard.vue';
import SubscriptionCard from '../components/SubscriptionCard.vue';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export default {
  name: 'Home',
  components: {
    Header,
    ArticleCard,
    SubscriptionCard
  },
  setup() {
    const articles = ref([]);
    const subscriptions = ref([]);
    const hasError = ref(false);
    
    const fetchArticles = async () => {
      try {
        const response = await axios.get(`${API_BASE_URL}articles.php`);
        if (response.data.success) {
          articles.value = response.data.data;
        } else {
          hasError.value = true;
        }
      } catch (error) {
        console.error('Error fetching articles:', error);
        hasError.value = true;
      }
    };
    
    const fetchSubscriptions = async () => {
      try {
        const response = await axios.get(`${API_BASE_URL}subscriptions.php`);
        if (response.data.success) {
          subscriptions.value = response.data.data;
        } else {
          hasError.value = true;
        }
      } catch (error) {
        console.error('Error fetching subscriptions:', error);
        hasError.value = true;
      }
    };
    
    onMounted(async () => {
      await Promise.all([fetchArticles(), fetchSubscriptions()]);
    });
    
    return {
      articles,
      subscriptions,
      hasError
    };
  }
};
</script>

<style scoped>
.error-message {
  padding: 3rem 0;
}

.error-box {
  background: white;
  padding: 3rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.error-box i {
  font-size: 48px;
  color: #ff6b6b;
  margin-bottom: 1rem;
}

.error-box h2 {
  color: #35495e;
  margin-bottom: 1rem;
}

.error-box p {
  color: #666;
  font-size: 18px;
}

.error-box a {
  color: #42b883;
  text-decoration: underline;
}

.error-box a:hover {
  color: #35495e;
}
</style>
