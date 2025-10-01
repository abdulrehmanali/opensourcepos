import SalesRegister from './SalesRegister'
import './App.css'

function App() {
  // Get props from window object that will be set by PHP
  const props = window.salesRegisterProps || {
    controller_name: 'sales',
    customer_id: null,
    modes: {},
    mode: 'sale',
    payment_options: {},
    cart: [],
    total: 0,
    amount_due: 0,
    config: {}
  };

  return <SalesRegister {...props} />
}

export default App
