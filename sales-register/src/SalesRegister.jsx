import React, { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';

const SalesRegister = ({ 
  controller_name = 'sales',
  customer_id = null,
  modes = {},
  mode = 'sale',
  payment_options = {},
  cart = [],
  total = 0,
  amount_due = 0,
  config = {}
}) => {
  // State management
  const [vehicleNo, setVehicleNo] = useState('');
  const [customerName, setCustomerName] = useState('');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [vehicleKilometer, setVehicleKilometer] = useState('');
  const [vehicleAvgOilKm, setVehicleAvgOilKm] = useState('');
  const [vehicleAvgKmDay, setVehicleAvgKmDay] = useState('');
  const [vehicleNextVisit, setVehicleNextVisit] = useState('');
  const [calculatedAvgPerDay, setCalculatedAvgPerDay] = useState('');
  const [customerSalesHistory, setCustomerSalesHistory] = useState([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);
  const [notifications, setNotifications] = useState([]);
  
  // Form states
  const [itemSearch, setItemSearch] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [unit, setUnit] = useState('pcs');
  const [price, setPrice] = useState('0.00');
  const [discount, setDiscount] = useState('0.00');
  const [discountToggle, setDiscountToggle] = useState(false);
  const [comment, setComment] = useState('');
  
  // Base price calculation states
  const [basePrice, setBasePrice] = useState(0);
  const [baseQuantity, setBaseQuantity] = useState(1);

  // Refs
  const typeWatchTimeouts = useRef({});
  const itemInputRef = useRef(null);

  // Utility Functions
  const typewatch = useCallback((callback, ms, key = 'default') => {
    if (typeWatchTimeouts.current[key]) {
      clearTimeout(typeWatchTimeouts.current[key]);
    }
    typeWatchTimeouts.current[key] = setTimeout(callback, ms);
  }, []);

  const showNotification = useCallback((message, type = 'info') => {
    const notification = { id: Date.now(), message, type };
    setNotifications(prev => [...prev, notification]);
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== notification.id));
    }, 5000);
  }, []);

  const calculateAveragePerDay = useCallback(() => {
    const avgOilKm = parseFloat(vehicleAvgOilKm) || 0;
    const avgKmDay = parseFloat(vehicleAvgKmDay) || 0;
    
    if (avgOilKm > 0 && avgKmDay > 0) {
      const avgPerDay = avgOilKm / avgKmDay;
      const days = Math.round(avgPerDay);
      setCalculatedAvgPerDay(`Next Visit in: ${days} ${days === 1 ? 'day' : 'days'}`);
      
      if (!vehicleNextVisit) {
        const today = new Date();
        const futureDate = new Date(today);
        futureDate.setDate(today.getDate() + days);
        const formattedDate = futureDate.toISOString().split('T')[0];
        setVehicleNextVisit(formattedDate);
      }
    } else {
      setCalculatedAvgPerDay('');
      if (!vehicleNextVisit) {
        setVehicleNextVisit('');
      }
    }
  }, [vehicleAvgOilKm, vehicleAvgKmDay, vehicleNextVisit]);

  // API Functions
  const saveVehicleData = useCallback(async (callback) => {
    const vehicleData = {
      vehicle_no: vehicleNo,
      kilometer: vehicleKilometer,
      last_avg_oil_km: vehicleAvgOilKm,
      last_avg_km_day: vehicleAvgKmDay,
      last_next_visit: vehicleNextVisit,
      last_customer_id: customer_id
    };

    if (vehicleData.vehicle_no) {
      try {
        await axios.post('/vehicles/save', vehicleData);
        showNotification('Vehicle data saved successfully', 'success');
        if (callback) callback(true);
      } catch (error) {
        showNotification('Warning: Vehicle data could not be saved', 'warning');
        if (callback) callback(false);
      }
    } else {
      if (callback) callback(false);
    }
  }, [vehicleNo, vehicleKilometer, vehicleAvgOilKm, vehicleAvgKmDay, vehicleNextVisit, customer_id, showNotification]);

  const loadVehicleData = useCallback((vehicle_no) => {
    typewatch(async () => {
      try {
        const response = await axios.get('/vehicles/getOrCreateByVehicleNo', {
          params: {
            vehicle_no,
            kilometer: vehicleKilometer,
            last_avg_oil_km: vehicleAvgOilKm,
            last_avg_km_day: vehicleAvgKmDay,
            last_next_visit: vehicleNextVisit,
            last_customer_id: customer_id
          }
        });

        if (response.data.success && response.data.vehicle) {
          const vehicle = response.data.vehicle;
          setVehicleKilometer(vehicle.kilometer || '');
          setVehicleAvgOilKm(vehicle.last_avg_oil_km || '');
          setVehicleAvgKmDay(vehicle.last_avg_km_day || '');
          setVehicleNextVisit(vehicle.last_next_visit || '');
          
          if (vehicle.last_customer_id) {
            loadCustomerById(vehicle.last_customer_id);
          }

          showNotification(
            response.data.created 
              ? `New vehicle created: ${vehicle.vehicle_no}`
              : 'Vehicle data loaded successfully',
            'success'
          );
        } else {
          setVehicleKilometer('');
          setVehicleAvgOilKm('');
          setVehicleAvgKmDay('');
          setVehicleNextVisit('');
          setCalculatedAvgPerDay('');
          showNotification(response.data.message || 'Error processing vehicle', 'warning');
        }
      } catch (error) {
        showNotification('Error loading vehicle data', 'danger');
      }
    }, 300, 'loadVehicle');
  }, [vehicleKilometer, vehicleAvgOilKm, vehicleAvgKmDay, vehicleNextVisit, customer_id, typewatch, showNotification]);

  const loadCustomerByPhone = useCallback((phone_number) => {
    typewatch(async () => {
      try {
        const response = await axios.get('/customers/byPhoneNumberOrCreateCustomer', {
          params: { phone_number, customer_name: customerName }
        });

        if (response.data.success && response.data.customer) {
          const customer = response.data.customer;
          
          // Select customer
          await axios.post('/sales/selectCustomer', {
            customer: customer.person_id
          });
          
          setCustomerName(`${customer.first_name} ${customer.last_name}`);
          
          showNotification(
            response.data.created 
              ? `New customer created and selected: ${customer.full_name}`
              : `Customer found and selected: ${customer.full_name}`,
            'success'
          );
          
          // Reload page or update state as needed
          window.location.reload();
        } else {
          showNotification(response.data.message || 'Error processing customer', 'danger');
        }
      } catch (error) {
        showNotification('Error searching for customer', 'danger');
      }
    }, 200, 'loadCustomer');
  }, [customerName, typewatch, showNotification]);

  const loadCustomerById = useCallback(async (customer_id) => {
    try {
      const response = await axios.get('/customers/customerById', {
        params: { customer_id }
      });

      if (response.data.success && response.data.customer) {
        const customer = response.data.customer;
        
        await axios.post('/sales/selectCustomer', {
          customer: customer_id
        });
        
        setCustomerName(`${customer.first_name} ${customer.last_name}`);
        setPhoneNumber(customer.phone_number || '');
        
        window.location.reload();
      }
    } catch (error) {
      console.error('Load Customer By ID Error:', error);
    }
  }, []);

  const loadCustomerSalesHistory = useCallback(async (customer_id) => {
    if (!customer_id) return;

    setIsLoadingHistory(true);
    try {
      const response = await axios.get('/sales/customerSalesHistory', {
        params: { customer_id }
      });

      if (response.data.success && response.data.sales) {
        setCustomerSalesHistory(response.data.sales);
      } else {
        setCustomerSalesHistory([]);
      }
    } catch (error) {
      showNotification('Error loading customer sales history', 'danger');
      setCustomerSalesHistory([]);
    } finally {
      setIsLoadingHistory(false);
    }
  }, [showNotification]);

  const searchItems = useCallback(async (term) => {
    try {
      const response = await axios.get(`/${controller_name}/itemSearch`, {
        params: { term }
      });
      return response.data;
    } catch (error) {
      return [];
    }
  }, [controller_name]);

  // Event Handlers
  const handleItemSelect = useCallback((item) => {
    setItemSearch(item.label);
    setBasePrice(parseFloat(item.price) || 0);
    setBaseQuantity(parseFloat(item.single_unit_quantity) || 1);
    setPrice(item.price || '0.00');
    setQuantity(item.single_unit_quantity || '1');
    setUnit(item.pack_name || 'pcs');
  }, []);

  const handleQuantityChange = useCallback((newQuantity) => {
    setQuantity(newQuantity);
    const qty = parseFloat(newQuantity) || 1;
    if (baseQuantity > 0) {
      const newPrice = (basePrice / baseQuantity) * qty;
      setPrice(newPrice.toFixed(2));
    }
  }, [basePrice, baseQuantity]);

  const handleVehicleNoChange = useCallback((value) => {
    setVehicleNo(value);
    if (value.length >= 2) {
      loadVehicleData(value);
    }
  }, [loadVehicleData]);

  const handlePhoneNumberChange = useCallback((value) => {
    setPhoneNumber(value);
    if (value && value.trim() !== '' && value.length >= 3) {
      loadCustomerByPhone(value);
    }
  }, [loadCustomerByPhone]);

  const handleAddItem = useCallback(async (e) => {
    e.preventDefault();
    
    const formData = {
      item: itemSearch,
      quantity,
      unit,
      price,
      discount,
      discount_toggle: discountToggle ? 1 : 0
    };

    try {
      await axios.post(`/${controller_name}/add`, formData);
      // Reset form
      setItemSearch('');
      setQuantity('1');
      setPrice('0.00');
      setDiscount('0.00');
      setDiscountToggle(false);
      
      // Reload page to update cart
      window.location.reload();
    } catch (error) {
      showNotification('Error adding item to cart', 'danger');
    }
  }, [itemSearch, quantity, unit, price, discount, discountToggle, controller_name, showNotification]);

  const handleFinishSale = useCallback(() => {
    saveVehicleData(() => {
      window.location.href = `/${controller_name}/complete`;
    });
  }, [saveVehicleData, controller_name]);

  // Effects
  useEffect(() => {
    calculateAveragePerDay();
  }, [calculateAveragePerDay]);

  useEffect(() => {
    if (customer_id) {
      loadCustomerSalesHistory(customer_id);
    }
  }, [customer_id, loadCustomerSalesHistory]);

  useEffect(() => {
    // Focus on item input when component mounts
    if (itemInputRef.current) {
      itemInputRef.current.focus();
    }
  }, []);

  // Component JSX
  return (
    <div className="sales-register">
      {/* Notifications */}
      <div className="notifications-container" style={{ position: 'fixed', top: '20px', right: '20px', zIndex: 1000 }}>
        {notifications.map(notification => (
          <div key={notification.id} className={`alert alert-${notification.type} alert-dismissible`}>
            {notification.message}
          </div>
        ))}
      </div>

      <div className="row">
        <div id="register_wrapper" className="col-sm-7">
          {/* Mode selection form would go here */}
          
          {/* Customer and Vehicle Info */}
          <div className="panel-body">
            <div className="row">
              <div className="col-sm-4">
                <div className="form-group form-group-sm">
                  <label className="required control-label" style={{ width: '100%' }}>
                    Vehicle No
                  </label>
                  <input
                    type="text"
                    className="form-control input-sm"
                    value={vehicleNo}
                    onChange={(e) => handleVehicleNoChange(e.target.value.toUpperCase())}
                    tabIndex="1"
                  />
                </div>
                <div className="form-group form-group-sm">
                  <label className="control-label" style={{ width: '100%' }}>
                    Kilometer
                  </label>
                  <input
                    type="number"
                    className="form-control input-sm"
                    value={vehicleKilometer}
                    onChange={(e) => setVehicleKilometer(e.target.value)}
                    tabIndex="4"
                  />
                </div>
                <div className="form-group form-group-sm">
                  <label className="control-label" style={{ width: '100%' }}>
                    Next Visit
                  </label>
                  <input
                    type="date"
                    className="form-control input-sm"
                    value={vehicleNextVisit}
                    onChange={(e) => setVehicleNextVisit(e.target.value)}
                    tabIndex="7"
                  />
                </div>
              </div>

              <div className="col-sm-4">
                <div className="form-group form-group-sm">
                  <label className="required control-label" style={{ width: '100%' }}>
                    Customer Name
                  </label>
                  <input
                    type="text"
                    className="form-control input-sm"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    tabIndex="2"
                  />
                </div>
                <div className="form-group form-group-sm">
                  <label className="control-label" style={{ width: '100%' }}>
                    Avg KM / Day
                  </label>
                  <input
                    type="number"
                    className="form-control input-sm"
                    value={vehicleAvgKmDay}
                    onChange={(e) => setVehicleAvgKmDay(e.target.value)}
                    tabIndex="6"
                  />
                </div>
                {calculatedAvgPerDay && (
                  <div style={{ color: '#2F4F4F', fontSize: '12px', marginTop: '5px' }}>
                    {calculatedAvgPerDay}
                  </div>
                )}
              </div>

              <div className="col-sm-4">
                <div className="form-group form-group-sm">
                  <label className="required control-label" style={{ width: '100%' }}>
                    Phone Number
                  </label>
                  <input
                    type="number"
                    className="form-control input-sm"
                    value={phoneNumber}
                    onChange={(e) => handlePhoneNumberChange(e.target.value)}
                    tabIndex="3"
                  />
                </div>
                <div className="form-group form-group-sm">
                  <label className="control-label" style={{ width: '100%' }}>
                    Avg KM/ Oil
                  </label>
                  <input
                    type="number"
                    className="form-control input-sm"
                    value={vehicleAvgOilKm}
                    onChange={(e) => setVehicleAvgOilKm(e.target.value)}
                    tabIndex="6"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Add Item Form */}
          <form onSubmit={handleAddItem} className="form-horizontal panel panel-default">
            <div className="panel-body row" style={{ display: 'flex', justifyContent: 'center', alignItems: 'end' }}>
              <div className="col-sm-4">
                <label className="control-label">Product Name / Barcode</label>
                <input
                  ref={itemInputRef}
                  type="text"
                  className="form-control input-sm"
                  value={itemSearch}
                  onChange={(e) => setItemSearch(e.target.value)}
                  required
                />
              </div>
              <div className="col-sm-2">
                <label className="control-label">Quantity</label>
                <div className="input-group">
                  <input
                    type="number"
                    className="form-control input-sm"
                    value={quantity}
                    onChange={(e) => handleQuantityChange(e.target.value)}
                    min="1"
                    step="1"
                    required
                  />
                  <span className="input-group-addon" style={{ padding: 0 }}>
                    <select
                      className="form-control input-sm"
                      value={unit}
                      onChange={(e) => setUnit(e.target.value)}
                      style={{ width: '45px', padding: 0, height: '33px', border: 0 }}
                    >
                      <option value="pcs">pcs</option>
                      <option value="kg">kg</option>
                      <option value="ltr">ltr</option>
                      <option value="mL">mL</option>
                    </select>
                  </span>
                </div>
              </div>
              <div className="col-sm-2">
                <label className="control-label">Price</label>
                <input
                  type="number"
                  className="form-control input-sm"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  step="0.01"
                  min="0"
                  required
                />
              </div>
              <div className="col-sm-2">
                <label className="control-label">Discount</label>
                <div className="input-group">
                  <input
                    type="text"
                    className="form-control input-sm"
                    value={discount}
                    onChange={(e) => setDiscount(e.target.value)}
                    onClick={(e) => e.target.select()}
                  />
                  <span className="input-group-btn">
                    <input
                      type="checkbox"
                      checked={discountToggle}
                      onChange={(e) => setDiscountToggle(e.target.checked)}
                      data-toggle="toggle"
                      data-size="small"
                      data-onstyle="success"
                      data-on="Rs"
                      data-off="%"
                    />
                  </span>
                </div>
              </div>
              <div className="col-sm-2">
                <label className="control-label">&nbsp;</label>
                <button type="submit" className="btn btn-primary btn-sm">
                  <span className="glyphicon glyphicon-plus"></span> Add
                </button>
              </div>
            </div>
          </form>

          {/* Cart and other components would go here */}
          
          {/* Finish Sale Button */}
          <div className="panel panel-default">
            <div className="panel-body">
              <button
                className="btn btn-sm btn-success pull-right"
                onClick={handleFinishSale}
              >
                <span className="glyphicon glyphicon-ok">&nbsp;</span>
                Complete Sale
              </button>
            </div>
          </div>
        </div>

        {/* Customer Sales History */}
        <div className="col-sm-5">
          <div style={{ marginTop: '2em' }}>
            <h4>Customer Sales History</h4>
            {isLoadingHistory && (
              <div>
                <span className="glyphicon glyphicon-refresh spinning"></span> Loading sales history...
              </div>
            )}
            <div>
              {customerSalesHistory.length > 0 ? (
                <table className="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>ID #</th>
                      <th>Date</th>
                      <th>Product</th>
                      <th>Kilometer</th>
                      <th>Avg Oil KM</th>
                      <th>Avg KM/Day</th>
                    </tr>
                  </thead>
                  <tbody>
                    {customerSalesHistory.map((sale, index) => 
                      sale.items.map((item, itemIndex) => (
                        <tr key={`${index}-${itemIndex}`}>
                          <td>{sale.sale_id || ''}</td>
                          <td>{sale.sale_time || ''}</td>
                          <td>{item.name || ''}</td>
                          <td>{sale.vehicle_kilometer || ''}</td>
                          <td>{sale.vehicle_avg_oil_km || ''}</td>
                          <td>{sale.vehicle_avg_km_day || ''}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              ) : (
                <div className="alert alert-info">No sales history found for this customer.</div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SalesRegister;