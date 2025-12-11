# React Integration Examples

## Installation

```bash
npm install axios
# or
npm install
```

---

## 1. Create Auth Context (Recommended)

### `src/context/AuthContext.jsx`

```javascript
import React, { createContext, useState, useContext, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext(null);

const API_URL = 'http://localhost/api';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [accessToken, setAccessToken] = useState(localStorage.getItem('access_token'));
  const [refreshToken, setRefreshToken] = useState(localStorage.getItem('refresh_token'));
  const [loading, setLoading] = useState(true);

  // Configure axios interceptor for automatic token refresh
  useEffect(() => {
    const requestInterceptor = axios.interceptors.request.use(
      (config) => {
        if (accessToken) {
          config.headers.Authorization = `Bearer ${accessToken}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    const responseInterceptor = axios.interceptors.response.use(
      (response) => response,
      async (error) => {
        const originalRequest = error.config;

        // If token expired, try to refresh
        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true;

          try {
            const newAccessToken = await refreshAccessToken();
            originalRequest.headers.Authorization = `Bearer ${newAccessToken}`;
            return axios(originalRequest);
          } catch (refreshError) {
            logout();
            return Promise.reject(refreshError);
          }
        }

        return Promise.reject(error);
      }
    );

    return () => {
      axios.interceptors.request.eject(requestInterceptor);
      axios.interceptors.response.eject(responseInterceptor);
    };
  }, [accessToken]);

  // Load user on mount
  useEffect(() => {
    if (accessToken) {
      loadUser();
    } else {
      setLoading(false);
    }
  }, []);

  const loadUser = async () => {
    try {
      const response = await axios.get(`${API_URL}/auth/me`);
      setUser(response.data.data);
    } catch (error) {
      console.error('Failed to load user:', error);
      logout();
    } finally {
      setLoading(false);
    }
  };

  const register = async (name, email, password) => {
    const response = await axios.post(`${API_URL}/auth/register`, {
      name,
      email,
      password,
    });
    return response.data;
  };

  const login = async (email, password) => {
    const response = await axios.post(`${API_URL}/auth/login`, {
      email,
      password,
    });

    const { access_token, refresh_token, user } = response.data.data;

    localStorage.setItem('access_token', access_token);
    localStorage.setItem('refresh_token', refresh_token);

    setAccessToken(access_token);
    setRefreshToken(refresh_token);
    setUser(user);

    return response.data;
  };

  const refreshAccessToken = async () => {
    const response = await axios.post(`${API_URL}/auth/refresh`, {
      refresh_token: refreshToken,
    });

    const { access_token } = response.data.data;

    localStorage.setItem('access_token', access_token);
    setAccessToken(access_token);

    return access_token;
  };

  const logout = async () => {
    try {
      await axios.post(`${API_URL}/auth/logout`, {
        refresh_token: refreshToken,
      });
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
      setAccessToken(null);
      setRefreshToken(null);
      setUser(null);
    }
  };

  const value = {
    user,
    accessToken,
    loading,
    register,
    login,
    logout,
    isAuthenticated: !!user,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

---

## 2. Wrap Your App

### `src/App.jsx`

```javascript
import { AuthProvider } from './context/AuthContext';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import ProtectedRoute from './components/ProtectedRoute';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
```

---

## 3. Protected Route Component

### `src/components/ProtectedRoute.jsx`

```javascript
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <div>Loading...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

export default ProtectedRoute;
```

---

## 4. Login Page

### `src/pages/Login.jsx`

```javascript
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      <h2>Login</h2>
      <form onSubmit={handleSubmit}>
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          type="password"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={loading}>
          {loading ? 'Loading...' : 'Login'}
        </button>
      </form>
    </div>
  );
};

export default Login;
```

---

## 5. Register Page

### `src/pages/Register.jsx`

```javascript
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Register = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await register(name, email, password);
      navigate('/login');
    } catch (err) {
      setError(err.response?.data?.message || 'Registration failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="register-container">
      <h2>Register</h2>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          placeholder="Name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          type="password"
          placeholder="Password (min 6 characters)"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          minLength={6}
        />
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={loading}>
          {loading ? 'Loading...' : 'Register'}
        </button>
      </form>
    </div>
  );
};

export default Register;
```

---

## 6. Dashboard with API Calls

### `src/pages/Dashboard.jsx`

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';

const API_URL = 'http://localhost/api';

const Dashboard = () => {
  const { user, logout } = useAuth();
  const [boardItems, setBoardItems] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchBoardItems();
  }, []);

  const fetchBoardItems = async () => {
    try {
      const response = await axios.get(`${API_URL}/board`);
      setBoardItems(response.data.data);
    } catch (error) {
      console.error('Failed to fetch board items:', error);
    } finally {
      setLoading(false);
    }
  };

  const createBoardItem = async (name, description) => {
    try {
      await axios.post(`${API_URL}/board`, {
        name,
        description,
        state: 'pending',
        assigned: user.id,
      });
      fetchBoardItems(); // Refresh list
    } catch (error) {
      console.error('Failed to create board item:', error);
    }
  };

  return (
    <div className="dashboard">
      <h1>Welcome, {user?.name}!</h1>
      <button onClick={logout}>Logout</button>

      <h2>Board Items</h2>
      {loading ? (
        <p>Loading...</p>
      ) : (
        <ul>
          {boardItems.map((item) => (
            <li key={item.id_board}>
              <h3>{item.name}</h3>
              <p>{item.description}</p>
              <span>Status: {item.state}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default Dashboard;
```

---

## 7. Alternative: Using Fetch API

If you prefer not to use axios:

```javascript
const login = async (email, password) => {
  const response = await fetch(`${API_URL}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  const data = await response.json();
  const { access_token, refresh_token, user } = data.data;

  localStorage.setItem('access_token', access_token);
  localStorage.setItem('refresh_token', refresh_token);

  setAccessToken(access_token);
  setRefreshToken(refresh_token);
  setUser(user);

  return data;
};

const fetchProtectedData = async () => {
  const token = localStorage.getItem('access_token');

  const response = await fetch(`${API_URL}/board`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });

  const data = await response.json();
  return data;
};
```

---

## Security Best Practices

1. **Store tokens in localStorage** (or sessionStorage for more security)
2. **Never expose tokens in URLs**
3. **Use HTTPS in production**
4. **Implement token refresh logic** to handle expired tokens
5. **Clear tokens on logout**
6. **Validate inputs on frontend** before sending to API

---

## Notes

- The access token expires in 15 minutes
- The refresh token expires in 7 days
- Axios interceptor automatically refreshes expired tokens
- All API calls include the Bearer token in headers
