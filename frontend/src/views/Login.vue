<template>
  <div class="login-container">
    <h2>Login to Dashboard</h2>

    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          v-model="form.email"
          required
          placeholder=""
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          v-model="form.password"
          required
          placeholder="Enter your password"
        />
      </div>

      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const form = reactive({
  email: '',
  password: ''
});

const isLoading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    // Make sure your Laravel API URL is correct (e.g., http://localhost:8000 )
    const response = await axios.post('http://localhost:8000/api/login', form );

    // 1. Extract the token from the response
    const token = response.data.token;

    // 2. Save the token to localStorage so it persists after page refresh
    localStorage.setItem('auth_token', token);

    // 3. Set Axios default header so all future requests use this token
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    // 4. Redirect the user to the products page or dashboard
    router.push('/products');

  } catch (error) {
    if (error.response && error.response.status === 401) {
      errorMessage.value = 'Invalid email or password.';
    } else {
      errorMessage.value = 'An error occurred during login. Please try again.';
    }
    console.error('Login error:', error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 50px auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
}
.form-group {
  margin-bottom: 15px;
}
.form-group label {
  display: block;
  margin-bottom: 5px;
}
.form-group input {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}
.error-message {
  color: red;
  margin-bottom: 15px;
  font-size: 0.9em;
}
button {
  width: 100%;
  padding: 10px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
button:disabled {
  background-color: #a0cbf8;
}
</style>
