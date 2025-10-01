# Sales Register React App Integration

This project integrates a React application with the OpenSourcePOS PHP application for the sales register page.

## Setup

The React app is located in `/sales-register/` and builds to `/public/sales-register/`.

### Initial Setup
```bash
cd sales-register
npm install
```

## Development Workflow

### 1. Development Mode (Hot Reloading)

For development with hot reloading, start the Vite dev server:

```bash
# Option 1: Use the provided script
./start-react-dev.sh

# Option 2: Manual start
cd sales-register
npm run dev
```

The dev server runs on `http://localhost:5173`

**To use development mode in your PHP page:**
- Add `?debug=true` to your URL, OR
- Set a debug cookie, OR  
- Set `ENVIRONMENT = 'development'` in your `.env`

When in development mode, the PHP page will automatically load from the Vite dev server with hot reloading.

### 2. Production Build

For production, build the React app:

```bash
cd sales-register
npm run build
```

This builds static files to `/public/sales-register/assets/`

### 3. How It Works

#### Development Mode:
- PHP detects if Vite dev server is running on `localhost:5173`
- Loads React app directly from dev server with hot reloading
- Changes to React code automatically refresh

#### Production Mode:
- PHP loads built static assets from `/public/sales-register/assets/`
- Optimized bundle for production use

#### Data Flow:
- PHP passes data to React via `window.salesRegisterProps`
- React app reads props from this global variable
- Supports all existing PHP variables (customer_id, cart, config, etc.)

## Files Structure

```
├── sales-register/              # React app source
│   ├── src/
│   │   ├── App.jsx             # Main app component
│   │   ├── SalesRegister.jsx   # Sales register component
│   │   └── main.jsx            # React entry point
│   ├── package.json
│   └── vite.config.js          # Build configuration
├── public/
│   └── sales-register/         # Built assets (auto-generated)
├── app/Views/sales/register.php # PHP page with React integration
└── start-react-dev.sh          # Development start script
```

## Development Tips

1. **Start development**: Run `./start-react-dev.sh`
2. **Access page**: Visit your sales register page with `?debug=true`
3. **Make changes**: Edit files in `sales-register/src/`
4. **See changes**: Hot reload happens automatically
5. **Deploy**: Run `npm run build` when ready for production

## API Integration

The React app uses axios for API calls to your PHP endpoints:
- `/vehicles/save` 
- `/customers/byPhoneNumber`
- `/sales/selectCustomer`
- etc.

All existing API endpoints work unchanged.

## Troubleshooting

- **Dev server not loading**: Check if `http://localhost:5173` is accessible
- **Production assets missing**: Run `npm run build` in sales-register directory
- **Props not passed**: Check `window.salesRegisterProps` in browser console
- **CORS issues**: Dev server has CORS enabled, but check network tabs for issues