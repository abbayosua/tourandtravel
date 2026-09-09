# Capacitor App (Android & iOS) — TourAndTravel

Panduan membuat aplikasi mobile menggunakan **Capacitor** yang terhubung ke codebase PHP TourAndTravel ini.

---

## 1. Arsitektur

```
┌─────────────────────────────────────┐
│  Capacitor App (Angular/Vue/React)  │
│  ┌───────────────────────────────┐  │
│  │  HTTP Client (fetch/axios)    │  │
│  │  Base URL → PHP backend       │  │
│  └───────────────────────────────┘  │
└──────────────┬──────────────────────┘
               │ HTTP/REST
┌──────────────▼──────────────────────┐
│  PHP Backend (Apache/XAMPP)         │
│  - Session-based auth (PHPSESSID)   │
│  - AJAX endpoints (JSON)            │
│  - MySQL (PDO)                      │
│  - Midtrans payment                 │
│  - WUZAPI WhatsApp                  │
└─────────────────────────────────────┘
```

---

## 2. Yang Perlu Dibangun: REST API Layer

Codebase saat ini **bukan REST API** — semua endpoint mengembalikan HTML atau JSON tercampur. Untuk Capacitor, kamu perlu membuat **dedicated API endpoints** yang konsisten.

### 2.1 Struktur API yang Dibutuhkan

```
api/
├── auth/
│   ├── POST /api/auth/register.php
│   ├── POST /api/auth/login.php
│   ├── POST /api/auth/logout.php
│   ├── POST /api/auth/forgot-password.php
│   └── POST /api/auth/reset-password.php
├── tours/
│   ├── GET  /api/tours/list.php           (search + filter + pagination)
│   ├── GET  /api/tours/detail.php?id=SLUG
│   ├── GET  /api/tours/dates.php?id=TOUR_ID
│   └── GET  /api/tours/itineraries.php?id=TOUR_ID
├── hotels/
│   ├── GET  /api/hotels/list.php           (search + filter + pagination)
│   ├── GET  /api/hotels/detail.php?id=SLUG
│   └── GET  /api/hotels/rooms.php?id=HOTEL_ID
├── flights/
│   ├── GET  /api/flights/search.php        (from, to, date, class, passengers)
│   └── GET  /api/flights/detail.php?id=ID
├── ferries/
│   ├── GET  /api/ferries/search.php
│   └── GET  /api/ferries/detail.php
├── trains/
│   ├── GET  /api/trains/search.php
│   └── GET  /api/trains/detail.php
├── transfers/
│   ├── GET  /api/transfers/search.php
│   └── GET  /api/transfers/detail.php
├── rental-cars/
│   ├── GET  /api/rental-cars/list.php
│   └── GET  /api/rental-cars/detail.php
├── attractions/
│   ├── GET  /api/attractions/list.php
│   └── GET  /api/attractions/detail.php
├── esim/
│   ├── GET  /api/esim/list.php
│   └── GET  /api/esim/detail.php
├── bookings/
│   ├── POST /api/bookings/create.php
│   ├── GET  /api/bookings/list.php
│   ├── GET  /api/bookings/detail.php?id=CODE
│   └── GET  /api/bookings/track.php?code=CODE
├── payments/
│   ├── POST /api/payments/create.php      (Midtrans Snap)
│   └── GET  /api/payments/status.php?order_id=ID
├── user/
│   ├── GET  /api/user/profile.php
│   ├── POST /api/user/profile.php         (update)
│   ├── GET  /api/user/wishlist.php
│   ├── POST /api/user/wishlist.php        (toggle)
│   ├── GET  /api/user/notifications.php
│   ├── POST /api/user/notifications/mark-read.php
│   ├── GET  /api/user/points.php
│   ├── GET  /api/user/wallet.php
│   ├── GET  /api/user/coupons.php
│   └── GET  /api/user/profiles.php        (passenger profiles)
│       POST /api/user/profiles.php
│       DELETE /api/user/profiles.php?id=X
├── reviews/
│   ├── GET  /api/reviews/list.php?tour_id=X
│   └── POST /api/reviews/submit.php
├── misc/
│   ├── GET  /api/misc/search.php?q=QUERY
│   ├── GET  /api/misc/currency-rates.php
│   ├── GET  /api/misc/newsletter.php
│   ├── GET  /api/misc/promo.php            (apply promo code)
│   ├── GET  /api/misc/social-proof.php
│   ├── GET  /api/misc/destinations.php     (city destinations)
│   ├── GET  /api/misc/faq.php
│   └── GET  /api/misc/blog.php
├── price-alerts/
│   ├── GET  /api/price-alerts/list.php
│   ├── POST /api/price-alerts/create.php
│   └── POST /api/price-alerts/delete.php
└── helpers/
    ├── cors.php          (CORS headers)
    ├── auth-check.php    (session/token validation)
    └── response.php      (standard JSON response)
```

---

### 2.2 Standard Response Format

Semua API harus konsisten:

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  }
}
```

Error:
```json
{
  "success": false,
  "error": "error_code",
  "message": "Human-readable message"
}
```

---

### 2.3 Authentication Strategy

Codebase sekarang pakai **PHP sessions**. Untuk Capacitor app, pilih salah satu:

#### Opsi A: Session-based (Paling Cepat)
```
1. App kirim POST /api/auth/login.php
2. Server set PHPSESSID cookie
3. App simpan cookie di Capacitor HTTP plugin
4. Semua request berikutnya include cookie
```

**Capacitor setup:**
```bash
npm install @capacitor-community/http
```

```typescript
import { Http } from '@capacitor-community/http';

// Login
const loginResult = await Http.request({
  method: 'POST',
  url: `${API_BASE}/api/auth/login.php`,
  headers: { 'Content-Type': 'application/json' },
  data: { email: 'user@test.com', password: '123456' }
});

// Subsequent requests — cookie otomatis disimpan
const tours = await Http.request({
  method: 'GET',
  url: `${API_BASE}/api/tours/list.php?page=1`
});
```

#### Opsi B: Token-based (Recommended untuk Production)
```
1. Buat JWT auth middleware baru
2. Login → return JWT token
3. App simpan token di SecureStorage
4. Semua request kirim Authorization: Bearer <token>
```

---

## 3. Setup Capacitor

### 3.1 Inisialisasi Project

```bash
# Buat project Ionic (atau React/Vue murni)
npm create @ionic/app tourandtravel-mobile
cd tourandtravel-mobile

# Tambahkan Capacitor
npx cap init "TourAndTravel" "com.tourandtravel.app" --web-dir www

# Tambahkan platform
npm install @capacitor/core @capacitor/cli
npx cap add android
npx cap add ios
npx cap add @capacitor-community/http
npx cap add @capacitor/preferences
npx cap add @capacitor/push-notifications
npx cap add @capacitor/camera
npx cap add @capacitor/geolocation
npx cap add @capacitor/splash-screen
npx cap add @capacitor/status-bar
```

### 3.2 Capacitor Config (`capacitor.config.ts`)

```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.tourandtravel.app',
  appName: 'Tour & Travel',
  webDir: 'www',
  server: {
    androidScheme: 'https',
    // Untuk development, bisa pakai live reload:
    // url: 'http://YOUR_IP:4200',
    // cleartext: true
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#0d6efd',
      showSpinner: true
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert']
    }
  }
};

export default config;
```

---

## 4. API Client Service

### 4.1 `services/api.service.ts`

```typescript
import { Http } from '@capacitor-community/http';
import { Preferences } from '@capacitor/preferences';

const API_BASE = 'https://yourdomain.com'; // Ganti dengan URL production

interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  meta?: { page: number; per_page: number; total: number; total_pages: number };
  error?: string;
  message?: string;
}

class ApiService {
  private baseUrl: string;

  constructor() {
    this.baseUrl = API_BASE;
  }

  private async request<T>(method: string, path: string, body?: any): Promise<T> {
    const options: any = {
      method,
      url: `${this.baseUrl}${path}`,
      headers: { 'Content-Type': 'application/json' }
    };

    if (body) options.data = body;

    try {
      const response = await Http.request(options);
      const json: ApiResponse<T> = response.data;
      if (!json.success) throw new Error(json.message || 'Request failed');
      return json.data as T;
    } catch (err: any) {
      throw new Error(err.message || 'Network error');
    }
  }

  // Auth
  async login(email: string, password: string) {
    return this.request('POST', '/api/auth/login.php', { email, password });
  }

  async register(data: { name: string; email: string; phone: string; password: string }) {
    return this.request('POST', '/api/auth/register.php', data);
  }

  // Tours
  async getTours(params: Record<string, string> = {}) {
    const qs = new URLSearchParams(params).toString();
    return this.request('GET', `/api/tours/list.php?${qs}`);
  }

  async getTourDetail(slug: string) {
    return this.request('GET', `/api/tours/detail.php?slug=${slug}`);
  }

  // Hotels
  async searchHotels(params: Record<string, string>) {
    const qs = new URLSearchParams(params).toString();
    return this.request('GET', `/api/hotels/list.php?${qs}`);
  }

  // Flights
  async searchFlights(params: Record<string, string>) {
    const qs = new URLSearchParams(params).toString();
    return this.request('GET', `/api/flights/search.php?${qs}`);
  }

  // Bookings
  async createBooking(data: any) {
    return this.request('POST', '/api/bookings/create.php', data);
  }

  async getMyBookings(page: number = 1) {
    return this.request('GET', `/api/bookings/list.php?page=${page}`);
  }

  // Wishlist
  async toggleWishlist(type: string, id: number) {
    return this.request('POST', '/api/user/wishlist.php', { item_type: type, item_id: id });
  }

  // Payments
  async createPayment(bookingType: string, bookingId: number) {
    return this.request('POST', '/api/payments/create.php', { booking_type: bookingType, booking_id: bookingId });
  }

  // Promo
  async applyPromo(code: string, subtotal: number) {
    return this.request('POST', '/api/misc/promo.php', { code, subtotal });
  }

  // Currency
  async getCurrencyRates() {
    return this.request('GET', '/api/misc/currency-rates.php');
  }
}

export const api = new ApiService();
```

---

## 5. Model Types

### 5.1 `models/tour.model.ts`

```typescript
export interface Tour {
  id: number;
  slug: string;
  title: string;
  description: string;
  city: string;
  country: string;
  price: number;
  price_currency: string;
  category: string;
  duration_days: number;
  duration_nights: number;
  rating: number;
  review_count: number;
  image_url: string;
  gallery: string[];
  highlights: string[];
  is_active: boolean;
}

export interface TourDate {
  id: number;
  tour_id: number;
  date: string;
  price: number;
  slots_available: number;
}

export interface Itinerary {
  id: number;
  tour_id: number;
  day_number: number;
  title: string;
  description: string;
  meals: string;
  accommodation: string;
}

export interface Booking {
  id: number;
  booking_code: string;
  tour_id: number;
  tour_title: string;
  tour_date: string;
  name: string;
  email: string;
  phone: string;
  participants: number;
  total_price: number;
  status: string; // pending, confirmed, paid, cancelled
  created_at: string;
}

export interface Hotel {
  id: number;
  slug: string;
  name: string;
  city: string;
  country: string;
  stars: number;
  price_per_night: number;
  price_currency: string;
  rating: number;
  review_count: number;
  image_url: string;
  amenities: string[];
}

export interface Flight {
  id: number;
  airline: string;
  flight_number: string;
  origin: string;
  destination: string;
  departure_time: string;
  arrival_time: string;
  duration: string;
  price: number;
  price_currency: string;
  class: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  points: number;
  wallet_balance: number;
  tier: string;
}
```

---

## 6. App Structure

```
src/
├── app/
│   ├── app.component.ts
│   ├── app.module.ts
│   ├── app-routing.module.ts
│   ├── pages/
│   │   ├── home/
│   │   ├── tours/
│   │   │   ├── tour-list/
│   │   │   └── tour-detail/
│   │   ├── hotels/
│   │   │   ├── hotel-list/
│   │   │   └── hotel-detail/
│   │   ├── flights/
│   │   │   └── flight-search/
│   │   ├── booking/
│   │   │   ├── booking-form/
│   │   │   ├── booking-list/
│   │   │   └── booking-success/
│   │   ├── auth/
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   └── forgot-password/
│   │   ├── profile/
│   │   │   ├── profile-page/
│   │   │   ├── wishlist/
│   │   │   ├── notifications/
│   │   │   ├── my-bookings/
│   │   │   ├── my-points/
│   │   │   ├── my-wallet/
│   │   │   └── my-coupons/
│   │   └── misc/
│   │       ├── search/
│   │       ├── faq/
│   │       └── blog/
│   ├── services/
│   │   ├── api.service.ts
│   │   ├── auth.service.ts
│   │   ├── storage.service.ts
│   │   └── push-notification.service.ts
│   ├── models/
│   │   ├── tour.model.ts
│   │   ├── hotel.model.ts
│   │   └── user.model.ts
│   ├── guards/
│   │   └── auth.guard.ts
│   └── components/
│       ├── tour-card/
│       ├── hotel-card/
│       ├── flight-card/
│       ├── price-display/
│       ├── search-bar/
│       └── bottom-nav/
```

---

## 7. Routing

```typescript
const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  { path: 'home', loadChildren: () => import('./pages/home/home.module').then(m => m.HomeModule) },
  { path: 'tours', loadChildren: () => import('./pages/tours/tour-list/tour-list.module').then(m => m.TourListModule) },
  { path: 'tours/:slug', loadChildren: () => import('./pages/tours/tour-detail/tour-detail.module').then(m => m.TourDetailModule) },
  { path: 'hotels', loadChildren: () => import('./pages/hotels/hotel-list/hotel-list.module').then(m => m.HotelListModule) },
  { path: 'hotels/:slug', loadChildren: () => import('./pages/hotels/hotel-detail/hotel-detail.module').then(m => m.HotelDetailModule) },
  { path: 'flights', loadChildren: () => import('./pages/flights/flight-search/flight-search.module').then(m => m.FlightSearchModule) },
  { path: 'booking', loadChildren: () => import('./pages/booking/booking-form/booking-form.module').then(m => m.BookingFormModule), canActivate: [AuthGuard] },
  { path: 'my-bookings', loadChildren: () => import('./pages/booking/booking-list/booking-list.module').then(m => m.BookingListModule), canActivate: [AuthGuard] },
  { path: 'login', loadChildren: () => import('./pages/auth/login/login.module').then(m => m.LoginModule) },
  { path: 'register', loadChildren: () => import('./pages/auth/register/register.module').then(m => m.RegisterModule) },
  { path: 'wishlist', loadChildren: () => import('./pages/profile/wishlist/wishlist.module').then(m => m.WishlistModule), canActivate: [AuthGuard] },
  { path: 'profile', loadChildren: () => import('./pages/profile/profile-page/profile-page.module').then(m => m.ProfilePageModule), canActivate: [AuthGuard] },
];
```

---

## 8. Key Pages → PHP Source Mapping

| Halaman Mobile | PHP Source | Endpoint API |
|---|---|---|
| Home | `index.php` | `GET /api/misc/home.php` |
| Tour List | `tours.php` → `tours-ajax.php` | `GET /api/tours/list.php` |
| Tour Detail | `tour-detail.php` | `GET /api/tours/detail.php` |
| Hotel List | `hotels.php` → `hotels-ajax.php` | `GET /api/hotels/list.php` |
| Hotel Detail | `hotel-detail.php` | `GET /api/hotels/detail.php` |
| Flight Search | `flights.php` → `flights-ajax.php` | `GET /api/flights/search.php` |
| Booking Form | `tour-detail.php` (form POST) | `POST /api/bookings/create.php` |
| My Bookings | `my-bookings.php` | `GET /api/bookings/list.php` |
| Login | `login.php` | `POST /api/auth/login.php` |
| Register | `register.php` | `POST /api/auth/register.php` |
| Wishlist | `wishlist.php` → `wishlist-ajax.php` | `GET/POST /api/user/wishlist.php` |
| Notifications | `notifications.php` → `ajax/notifications.php` | `GET /api/user/notifications.php` |
| Points | `my-points.php` | `GET /api/user/points.php` |
| Wallet | `wallet.php` | `GET /api/user/wallet.php` |
| Coupons | `my-coupons.php` | `GET /api/user/coupons.php` |
| Profiles | `my-profiles.php` → `profile-ajax.php` | `GET/POST /api/user/profiles.php` |
| Reviews | `review-submit.php` | `POST /api/reviews/submit.php` |
| Price Alerts | `my-alerts.php` → `price-alert-ajax.php` | `GET/POST /api/price-alerts/` |
| FAQ | `faq.php` | `GET /api/misc/faq.php` |
| Blog | `blog.php` | `GET /api/misc/blog.php` |
| Promo | `apply-promo-ajax.php` | `POST /api/misc/promo.php` |
| Currency | `ajax/currency-rates.php` | `GET /api/misc/currency-rates.php` |
| Search | `search-ajax.php` | `GET /api/misc/search.php` |
| Ferries | `ferries.php` | `GET /api/ferries/search.php` |
| Trains | `trains.php` | `GET /api/trains/search.php` |
| Transfers | `transfers.php` | `GET /api/transfers/search.php` |
| Rental Cars | `rental-cars.php` | `GET /api/rental-cars/list.php` |
| Attractions | `attractions.php` | `GET /api/attractions/list.php` |
| eSIM | `esim.php` | `GET /api/esim/list.php` |

---

## 9. Payment Integration (Midtrans)

### 9.1 Flow

```
App → POST /api/payments/create.php
   → Server buat Midtrans Snap token
   → Return { token: "xxx", redirect_url: "..." }

App → Buka Midtrans Snap UI (WebView / SDK)
   → User bayar
   → Midtrans webhook → webhook-midtrans.php
   → Status update otomatis

App → GET /api/payments/status.php?order_id=XXX
   → Cek status pembayaran
```

### 9.2 Midtrans Snap di Capacitor

```typescript
import { Browser } from '@capacitor/browser';

async function payWithMidtrans(token: string, redirectUrl: string) {
  // Buka Midtrans Snap UI di browser WebView
  await Browser.open({ url: redirectUrl });

  // Listen untuk callback
  Browser.addListener('browserFinished', () => {
    // Cek status pembayaran
    checkPaymentStatus(orderId);
  });
}
```

---

## 10. Push Notifications

```bash
npx cap add @capacitor/push-notifications
```

### 10.1 Setup

```typescript
import { PushNotifications } from '@capacitor/push-notifications';

async function initPush() {
  const permission = await PushNotifications.requestPermissions();
  if (permission.receive !== 'granted') return;

  await PushNotifications.register();

  PushNotifications.addListener('registration', (token) => {
    // Kirim token ke server untuk disimpan
    api.registerPushToken(token.value);
  });

  PushNotifications.addListener('pushNotificationReceived', (notification) => {
    // Tampilkan notifikasi saat app foreground
    console.log('Push received:', notification);
  });

  PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
    // Handle tap notifikasi — navigate ke halaman terkait
    const data = action.notification.data;
    if (data.type === 'booking') {
      router.navigate(['/my-bookings', data.booking_code]);
    }
  });
}
```

### 10.2 Server-side: Simpan Token

Tambahkan kolom `push_token` di tabel `users` dan endpoint `POST /api/user/push-token.php`.

---

## 11. Offline Support

```typescript
import { Preferences } from '@capacitor/preferences';

class StorageService {
  async set(key: string, value: any) {
    await Preferences.set({ key, value: JSON.stringify(value) });
  }

  async get<T>(key: string): Promise<T | null> {
    const { value } = await Preferences.get({ key });
    return value ? JSON.parse(value) : null;
  }

  async remove(key: string) {
    await Preferences.remove({ key });
  }
}

// Cache data untuk offline
async function cacheTours(tours: Tour[]) {
  await storage.set('cached_tours', tours);
}

async function getCachedTours(): Promise<Tour[]> {
  return (await storage.get<Tour[]>('cached_tours')) || [];
}
```

---

## 12. Build & Deploy

### 12.1 Build

```bash
# Build web assets
npm run build

# Sync ke Capacitor
npx cap sync

# Build Android
npx cap open android    # Buka di Android Studio
# Atau
npx cap build android

# Build iOS
npx cap open ios        # Buka di Xcode
# Atau
npx cap build ios
```

### 12.2 Android

```bash
npx cap add android
npx cap sync android
npx cap open android
# Build APK di Android Studio → Build → Build Bundle/APK
```

### 12.3 iOS

```bash
npx cap add ios
npx cap sync ios
npx cap open ios
# Build di Xcode → Product → Archive
```

---

## 13. Development Workflow

### 13.1 Live Reload (Development)

```bash
# 1. Jalankan dev server frontend
npm start   # http://localhost:4200

# 2. Edit capacitor.config.ts untuk live reload
server: {
  url: 'http://192.168.x.x:4200', // IP komputer kamu
  cleartext: true
}

# 3. Run di device
npx cap run android --livereload --external
npx cap run ios --livereload --external
```

### 13.2 API Development

```bash
# Jalankan PHP backend di XAMPP
# URL: http://localhost/tourandtravel

# Di Capacitor, set API_BASE ke:
# Development: http://192.168.x.x/tourandtravel (IP LAN)
# Production: https://yourdomain.com
```

---

## 14. Checklist Implementasi

- [ ] Buat folder `api/` di root project PHP
- [ ] Buat `api/helpers/cors.php` (CORS headers untuk mobile)
- [ ] Buat `api/helpers/auth-check.php` (session/token check)
- [ ] Buat `api/helpers/response.php` (standard JSON response)
- [ ] Implement auth API (register, login, logout)
- [ ] Implement tours API (list, detail, dates, itineraries)
- [ ] Implement hotels API (list, detail, rooms)
- [ ] Implement flights API (search, detail)
- [ ] Implement bookings API (create, list, detail, track)
- [ ] Implement payments API (create, status)
- [ ] Implement user API (profile, wishlist, notifications, points, wallet)
- [ ] Implement misc API (search, currency, promo, FAQ, blog)
- [ ] Setup Capacitor project
- [ ] Implement API client service
- [ ] Build all pages
- [ ] Test on Android emulator
- [ ] Test on iOS simulator
- [ ] Setup push notifications
- [ ] Test payment flow
- [ ] Deploy to Play Store & App Store

---

## 15. Notes Penting

1. **CORS**: PHP backend harus set `Access-Control-Allow-Origin: *` atau domain spesifik
2. **HTTPS**: Production wajib HTTPS (Capacitor Android default pakai `https` scheme)
3. **Images**: Pastikan semua image URL absolute (bukan relative)
4. **BASE_URL**: Di `includes/config.php`, ganti `http://localhost/tourandtravel` dengan domain production
5. **Session**: Jika pakai session auth, set cookie domain & secure flag di production
6. **Midtrans**: Webhook tetap ke PHP backend, bukan ke mobile app
7. **File Upload**: Untuk passport upload di booking, pakai `@capacitor/camera` untuk ambil foto
