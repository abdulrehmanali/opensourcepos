# Payment Auto-Calculation Feature

## Overview
The Sales Register component now includes automatic cash payment calculation. When users enter payment amounts for non-cash payment methods, the cash payment field is automatically populated with the remaining balance needed to complete the sale.

## How It Works

### 1. **Dynamic Total Calculation** (`calculateSaleTotal`)
- A new callback function calculates the total sale amount from all cart items
- Takes into account discounts and handles both direct totals and calculated totals
- This total is used as the base for auto-calculation

### 2. **Smart Payment Input Handler** (`handlePaymentInputChange`)
- Detects when a user enters a value in any payment method field (except cash)
- Identifies the "cash" payment option key (case-insensitive: "cash", "Cash", "CASH", etc.)
- Calculates the remaining amount: `Sale Total - Sum of Other Payments`
- Auto-fills the cash field with the remaining amount (minimum 0)
- Only updates cash field if it exists in payment options

### 3. **Enhanced UI**
Cash payment field now includes:
- **Visual Distinction**: Light green background (#f0fff4) and green border to highlight the auto-calculated field
- **Read-Only**: Cash field is read-only, preventing manual user input
- **Labels**: Each payment method now has a label above the input field
- **Tooltip**: Hovering over the cash field displays helpful text explaining auto-calculation
- **Bold Label**: The "Cash" label is bolded in green for easy identification

## User Experience Flow

1. **Add items to cart** → Sale total is displayed
2. **Enter payment for other methods** (Credit Card, Check, etc.)
   - → Cash payment is automatically calculated and filled
   - → Total payment amount updates in real-time
3. **View auto-calculated cash amount** in green highlighted field
4. **Apply payments** → Submits all payment entries to backend

## Example Scenario

- **Sale Total**: 1000.00
- **User enters Credit Card**: 400.00
- **Cash auto-fills to**: 600.00 (1000 - 400)
- **User enters Check**: 300.00
- **Cash auto-updates to**: 300.00 (1000 - 400 - 300)
- **User clicks Apply Payments** → All three payments are submitted

## Technical Details

### Modified Function
```javascript
const handlePaymentInputChange = useCallback(
  (key, value) => {
    // Updates payment inputs state
    // Auto-calculates cash based on:
    // - Sale total from cart items
    // - Sum of all other payment methods
    // - Remaining amount = Sale Total - Other Payments
  },
  [payment_options, calculateSaleTotal]
);
```

### Key Features
- **Null-safe**: Safely handles missing payment options or cash key
- **Real-time**: Updates instantly as users modify payment amounts
- **Zero-aware**: Clears cash field if already covered by other payments (shows empty instead of 0)
- **Locale-friendly**: Handles both comma and period decimal separators

## Browser Compatibility
- Works with all modern browsers supporting:
  - ES6 React Hooks (useState, useCallback, useMemo)
  - Template literals
  - Object spread operator

## No Backend Changes Required
This feature is purely client-side and doesn't require any backend modifications. All payments are still submitted individually as before via the existing `applyPayments` function.

## Notes
- The cash field remains editable at the backend layer; the UI just prevents direct user editing
- If a "cash" payment option key doesn't exist, auto-calculation is skipped gracefully
- The cash field only updates when OTHER payment methods change, maintaining focus on manual fields
