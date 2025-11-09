# Creatim Frontend Setup

## Completed Implementation

### Dependencies Installed
- **Vue 3** - Frontend framework
- **Vuex 4** - State management
- **Vue Router 4** - Routing
- **Axios** - HTTP client for API calls
- **Font Awesome** - Icons (cart icon and buttons)
- **SASS** - CSS preprocessor
- **js-cookie** - Cookie management
- **js-md5** - MD5 hash generation for session IDs

### SCSS ITCSS Architecture
Created a complete ITCSS folder structure:
```
src/styles/
├── settings/      (_variables.scss)
├── tools/         (_mixins.scss)
├── generic/       (_reset.scss)
├── elements/      (_base.scss)
├── objects/       (_container.scss)
├── components/    (_header.scss, _card.scss, _section.scss)
├── utilities/     (_helpers.scss)
└── main.scss      (imports all)
```

### Components Created
1. **Header.vue** - Logo on left, Phone display and Cart button with badge on right
2. **ArticleCard.vue** - Card component for articles with "Add to Cart" button
3. **SubscriptionCard.vue** - Card component for subscriptions with "Add to Cart" button

### Pages
1. **Home.vue** - Two sections:
   - Articles section (fetches from `http://localhost:8000/api/v1/articles.php`)
   - Subscriptions section (fetches from `http://localhost:8000/api/v1/subscriptions.php`)
   - Shows error message with contact email if API calls fail
2. **Cart.vue** - Shopping cart page with full item details:
   - Fetches actual item data from API using `.env` URL
   - Displays item title, type (Article/Physical/Digital Subscription), and prices
   - Shows price for each item (one of each allowed)
   - Phone number input form with real-time validation
   - Total items count and total price summary
   - Updates Vuex state and database on all cart operations
   - Remove individual items or clear entire cart

### Features Implemented
✅ **Environment Configuration** - `.env` file with `VITE_API_BASE_URL=http://localhost:8000/api/v1/`
✅ **Vuex Store** - Manages cart state with database persistence (stores objects with id and amount)
  - `cart.articles`: Array of article cart items [{ id, amount }]
  - `cart.subscriptions`: Array of subscription cart items [{ id, amount }]
  - `purchases.articles`: Processed/purchased articles
  - `purchases.subscriptions`: Active subscriptions from latest order
  - Cart badge count: Sum of articles + subscriptions amounts
✅ **Session Management** - Reads cookies on refresh, fetches cart and purchases if exists, creates new session if not
✅ **Database Cart Storage** - Cart items stored in database, no cookie storage
✅ **Cart Badge** - Shows total item count (sum of all amounts) in header
✅ **Add to Cart** - Adds item to cart
  - Articles: Multiple articles allowed (one of each type)
  - Subscriptions: Only one subscription allowed (latest replaces previous)
✅ **In Cart Detection** - Button changes to "In Cart" and disables when item already added
  - Articles: Shows "In Cart" when article ID found in cart.articles
  - Subscriptions: Shows "In Cart" when subscription ID found in cart.subscriptions
  - Cart loaded from server automatically identifies item types via API lookup
✅ **Font Awesome Icons** - Cart icon and button icons
✅ **Axios Integration** - Fetches articles and subscriptions from backend
✅ **EUR Currency** - Prices displayed with € symbol, converted from cents (price/100)
✅ **Error Handling** - Shows "API communication error, please contact us on info@creatim.com" when API fails
✅ **404 Detection** - Shows "Error in api configuration" alert when API endpoint returns 404
✅ **Order Submission** - Checkout button submits order, marks cart as submitted, generates new session
✅ **Order Processing** - Backend calculates total price, creates order with "NEEDS TO BE PROCESSED" status
✅ **Cart Protection** - Submitted carts cannot be modified (403 error if attempted)
✅ **Double-Submission Prevention** - Cart locked after order is placed
✅ **Phone Number Storage** - Cart can store customer phone (digits only, validated on server)
✅ **Auto Phone Transfer** - Phone from cart automatically used in order if not explicitly provided
✅ **Phone Display in Header** - Phone number displayed in header (read-only), loaded from Vuex state
✅ **Phone Input Form in Cart** - Dedicated form in cart page for entering phone number
✅ **Phone Form Validation** - Client-side strips non-digits, server-side validates numeric only
✅ **Phone State Refresh** - After submission, fetches updated state from API and updates Vuex
✅ **Phone Form Feedback** - Success/error messages with auto-dismiss after 3 seconds
✅ **Checkout Validation** - Checkout button disabled when phone is empty or contains non-numeric characters
✅ **Checkout Warning** - Yellow warning message displayed when phone is invalid
✅ **Purchases Tracking** - Fetches processed orders from backend and displays "READ" button
✅ **Auto-Refresh Purchases** - Updates purchase state on page load and every 10 seconds
✅ **Purchase Detection** - Articles/subscriptions with status "PROCESSED" show as purchased

### How to Run
```bash
cd /home/winsucker/IdeaProjects/creatim/frontend/creatim
npm run dev
```

The app will be available at the URL shown in the terminal (typically http://localhost:5173)

### Environment Configuration
The `.env` file contains:
```
VITE_API_BASE_URL=http://localhost:8000/api/v1/
```

### Pricing Format
- Backend stores prices in **cents** (integer)
- Frontend converts to euros: `price / 100`
- Displays with **€** symbol (e.g., €12.99)
- Articles: Shows price as €X.XX
- Subscriptions: Shows price as €X.XX/month

### Cart Data Structure
Cart items are stored as objects containing:
- `id` - The item ID (article or subscription)
- `amount` - Quantity of this item in cart

Example: `[{id: 8, amount: 2}, {id: 10, amount: 1}]`

### Session & Database Persistence
**Backend:**
- `Carts` table stores: id, session, pwd, cart (JSON), date_modified, submitted (boolean), phone (integer)
- `Orders` table stores: id, order_number, customer_phone, status, price, articles (JSON), subscription_pkg (JSON), session_id, date_created
- `new-session.php` API creates new session with MD5 hashes and empty cart in DB
- `update-cart.php` API validates credentials and reads/updates cart and phone in DB
- `order.php` API processes checkout, uses phone from cart if not provided, marks cart as submitted, creates order
- `Cart.php` lib handles cart database operations including phone number validation
- `Order.php` lib handles order processing and price calculation

**Frontend:**
- On first visit: Calls `new-session.php` API, stores credentials in cookies, creates DB entry
- On subsequent visits: Validates session with backend, loads cart and phone from database
- If validation fails: Shows "Reading cart error" alert and creates new session
- All cart operations sync to database in real-time via `update-cart.php`
- Cart and phone persist only in database (no cookie storage)
- Only session credentials (SessionId and pwd) stored in cookies
- Phone stored in Vuex state: accessible via `store.getters.phone`
- Phone displayed in header (read-only) when available

### Cart Data Fetching
When you view the cart, the app:
1. Reads cart items (IDs and amounts) from Vuex state
2. For each item, fetches full data from backend API:
   - First tries `articles.php?id=X`
   - If not found, tries `subscriptions.php?id=X`
3. Displays complete item information with prices
4. Calculates subtotals and grand total in euros

All cart operations (add, remove, update amount, clear) automatically update:
- ✅ Vuex state
- ✅ Database (via API)
- ✅ Cart badge count

### Checkout Process
**Prerequisites:**
- ⚠️ **Phone number required**: Checkout button disabled until valid phone entered
- Phone must contain only numeric digits

When "Checkout" button is pressed:
1. Validates session credentials exist
2. Validates phone number exists and is numeric (frontend)
3. Sends POST request to `order.php` with session_id and pwd
4. Backend validates session credentials (session_id + pwd only)
5. Backend retrieves phone from cart database (required)
6. Backend returns 400 error if phone is empty
7. Backend calculates total price from articles/subscriptions
8. Backend marks cart as submitted (submitted = 1)
9. Backend creates order row with status "NEEDS TO BE PROCESSED"
9. Frontend shows alerts:
   - **Success**: "Ordered successfully"
   - **Error**: "Error in processing order, try later"
10. On success:
   - Clears local cart state in Vuex (no server sync needed)
   - Cart row is already marked submitted=1 by backend
   - Session and credentials remain unchanged
   - User can continue using same session for viewing purchases

### Purchases System

**API Endpoint:** `/api/v1/purchases.php`

**How It Works:**
1. Frontend fetches purchases on page load and every 10 seconds
2. Backend queries orders table for `status = "PROCESSED"`
3. Validates only `session_id` and `pwd` (phone not checked)
4. Returns ALL processed orders for the session:
   - **Articles**: Aggregated from ALL processed orders (amounts summed)
   - **Subscriptions**: From LATEST processed order only (most recent by date)
5. Frontend updates Vuex state with purchase data
6. Cards check if item is purchased and show "READ" button instead of "Add to Cart"

**Response Format:**
```json
{
  "success": true,
  "data": {
    "articles": [
      { "id": 1, "amount": 2 },
      { "id": 5, "amount": 1 }
    ],
    "subscriptions": [
      { "id": 3 }
    ]
  }
}
```

**Button States:**

**Articles:**
- **Not purchased, not in cart**: "Add to Cart" (blue, enabled)
- **In cart**: "In Cart" (gray, disabled)
- **Purchased (PROCESSED)**: "READ" (cyan, enabled, clickable)

**Subscriptions:**
- **Not purchased, not in cart**: "Add to Cart" (blue, enabled)
- **In cart**: "In Cart" (gray, disabled)
- **Subscribed (PROCESSED)**: "Subscribed" (orange, enabled, clickable) ⭐

### Session Initialization Flow

**On Page Load/Refresh:**
1. **Check Cookies First**:
   - Read `SessionId` and `pwd` from browser cookies
   
2. **If cookies exist**:
   - Try to use existing session
   - **Fetch cart**: `GET /api/v1/update-cart.php?session_id=...&pwd=...`
   - **If success: true**: Load cart items and phone into store
   - **If success: false**: Session invalid, create new session
   - **If fetch fails or returns error**: Create new session (fallback)
   - **Fetch purchases**: `GET /api/v1/purchases.php?session_id=...&pwd=...`
   - If cart is submitted, create new session
   
3. **If no cookies**:
   - **Create new session**: `GET /api/v1/new-session.php`
   - Receive `{session_id, pwd}` from backend
   - Set cookies with new credentials (expires in 365 days)
   - Set session in Vuex store
   
4. **Start auto-refresh**:
   - Fetch purchases every 10 seconds

**Result**: User's cart and purchases are restored on refresh, or new session is created seamlessly

### Security & Cart Protection

**Submitted Cart Auto-Reset:**
- When cart is submitted (order placed), it's marked with `submitted = 1`
- Orders are permanently linked to the submitted cart state snapshot
- When user tries to add new items, backend automatically resets the cart:
  - `Cart::updateCart()` detects `submitted = 1`
  - Calls `resetSubmittedCart()` to set `submitted = 0` and clear cart
  - Then proceeds with normal cart update
  - Previous order remains in orders table with original cart snapshot
- No 403 errors - backend handles reset transparently
- Session credentials remain unchanged (no new session created)
- User can place unlimited orders with the same session
- Frontend: On page load with submitted cart, clears local cart
- API includes `submitted` field in cart responses (0 or 1)

**Session Persistence After Order:**
- Session credentials (SessionId and pwd) remain unchanged after checkout
- User keeps same session to view their purchases
- Cart is cleared locally but session is not regenerated
- Purchases are tied to session_id and phone number
- User can see their order status and purchased content with same session

**Security Features:**
✅ Session validation required for all cart/order operations
✅ Server-side price calculation (cannot be manipulated by client)
✅ Database transactions ensure atomic cart submission + order creation
✅ Double-submission protection via submitted flag
✅ Audit trail: Orders permanently linked to sessions

### API Endpoints

**Session Management:**
- `GET /api/v1/new-session.php` - Creates new session, returns `{session_id, pwd}`
  
**Cart Management:**
- `GET /api/v1/update-cart.php?session_id=X&pwd=Y` - Get cart data
  - Returns: `{cart, date_modified, submitted, phone}` where submitted is 0 or 1, phone is integer or null
- `POST /api/v1/update-cart.php` - Update cart (body: `{session_id, pwd, cart, phone?}`)
  - Phone must contain only digits (validated on server, stored as integer)
  - Returns 400 if phone contains non-numeric characters
  - Returns 403 if cart is already submitted

**Order Management:**
- `POST /api/v1/order.php` - Submit order (body: `{session_id, pwd, customer_phone?}`)
  - Validates session credentials
  - Uses phone from cart if customer_phone not provided in request
  - Calculates total price from articles/subscriptions
  - Marks cart as submitted
  - Creates order with status "NEEDS TO BE PROCESSED"
  - Returns order details

**Product Data:**
- `GET /api/v1/articles.php` - Get all articles
- `GET /api/v1/articles.php?id=X` - Get specific article
- `GET /api/v1/subscriptions.php` - Get all subscriptions  
- `GET /api/v1/subscriptions.php?id=X` - Get specific subscription

### API Error Handling
If API calls to `articles.php` or `subscriptions.php` fail, the app displays:
> **API communication error, please contact us on info@creatim.com**
