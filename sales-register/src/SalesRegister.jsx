import React, { useState, useEffect, useCallback, useRef, useMemo } from "react";
import axios from "axios";

const SalesRegister = ({
	controller_name = "sales",
	customer_id = null,
	modes = {},
	mode = "sale",
	payment_options = {},
	cart = [],
	total = 0,
	config = {},
	selected_vehicle_id = null,
	selected_vehicle_no = "",
}) => {
	const BASE_URL = "http://localhost/public";
	console.log(customer_id);
	// State management
	const [vehicleNo, setVehicleNo] = useState(selected_vehicle_no || "");
	const [customerName, setCustomerName] = useState("");
	const [phoneNumber, setPhoneNumber] = useState("");
	const [vehicleKilometer, setVehicleKilometer] = useState("");
	const [vehicleAvgOilKm, setVehicleAvgOilKm] = useState("");
	const [vehicleAvgKmDay, setVehicleAvgKmDay] = useState("");
	const [vehicleNextVisit, setVehicleNextVisit] = useState("");
	const [mechanicName, setMechanicName] = useState("");
	const [calculatedAvgPerDay, setCalculatedAvgPerDay] = useState("");
	const [customerSalesHistory, setCustomerSalesHistory] = useState([]);
	const [isLoadingHistory, setIsLoadingHistory] = useState(false);
	const [notifications, setNotifications] = useState([]);
	const [cartItems, setCartItems] = useState(cart || []);
	const [currentCustomerId, setCurrentCustomerId] = useState(customer_id);

	// Form states
	const [itemSearch, setItemSearch] = useState("");
	const [itemId, setItemId] = useState("");
	const [quantity, setQuantity] = useState("1");
	const [unit, setUnit] = useState("pcs");
	const [price, setPrice] = useState("0.00");
	const [discount, setDiscount] = useState("0.00");
	const [discountToggle, setDiscountToggle] = useState(false);
	const [comment, setComment] = useState("");

	// Edit item state - track if we're editing and which item
	const [isEditingItem, setIsEditingItem] = useState(false);
	const [currentEditingItem, setCurrentEditingItem] = useState(null);

	// Base price calculation states
	const [basePrice, setBasePrice] = useState(0);
	const [baseQuantity, setBaseQuantity] = useState(1);
	const [selectedItemRaw, setSelectedItemRaw] = useState(null);

	// Refs
	const typeWatchTimeouts = useRef({});
	const itemInputRef = useRef(null);
	const vehicleInputRef = useRef(null);
	const customerNameRef = useRef(null);
	const phoneInputRef = useRef(null);
	const initialCustomerLoaded = useRef(false);
	const lastLoadedVehicleNo = useRef(null);
	// selected_vehicle_id is available via props and used below if needed

	console.log(vehicleNo);
	// Utility Functions
	const typewatch = useCallback((callback, ms, key = "default") => {
		if (typeWatchTimeouts.current[key]) {
			clearTimeout(typeWatchTimeouts.current[key]);
		}
		typeWatchTimeouts.current[key] = setTimeout(callback, ms);
	}, []);

	const showNotification = useCallback((message, type = "info") => {
		const notification = { id: Date.now(), message, type };
		setNotifications((prev) => [...prev, notification]);
		setTimeout(() => {
			setNotifications((prev) => prev.filter((n) => n.id !== notification.id));
		}, 5000);
	}, []);

	const calculateAveragePerDay = useCallback(() => {
		const avgOilKm = parseFloat(vehicleAvgOilKm) || 0;
		const avgKmDay = parseFloat(vehicleAvgKmDay) || 0;

		if (avgOilKm > 0 && avgKmDay > 0) {
			const avgPerDay = avgOilKm / avgKmDay;
			let days = Math.round(avgPerDay);
			// Cap at 6 months (180 days)
			days = Math.min(days, 180);
			setCalculatedAvgPerDay(`Next Visit in: ${days} ${days === 1 ? "day" : "days"}`);

			// Always update vehicleNextVisit based on calculated days
			const today = new Date();
			const futureDate = new Date(today);
			futureDate.setDate(today.getDate() + days);
			const formattedDate = futureDate.toISOString().split("T")[0];
			setVehicleNextVisit(formattedDate);
		} else {
			setCalculatedAvgPerDay("");
			setVehicleNextVisit("");
		}
	}, [vehicleAvgOilKm, vehicleAvgKmDay]);

	// API Functions
	const saveVehicleData = useCallback(
		async (callback) => {
			const vehicleData = {
				vehicle_no: vehicleNo,
				kilometer: vehicleKilometer,
				last_avg_oil_km: vehicleAvgOilKm,
				last_avg_km_day: vehicleAvgKmDay,
				last_next_visit: vehicleNextVisit,
				last_customer_id: currentCustomerId,
			};

			if (vehicleData.vehicle_no) {
				try {
					await axios.post(BASE_URL + "/vehicles/save", vehicleData);
					showNotification("Vehicle data saved successfully", "success");
					if (callback) callback(true);
				} catch (error) {
					showNotification("Warning: Vehicle data could not be saved", "warning");
					if (callback) callback(false);
				}
			} else {
				if (callback) callback(false);
			}
		},
		[
			vehicleNo,
			vehicleKilometer,
			vehicleAvgOilKm,
			vehicleAvgKmDay,
			vehicleNextVisit,
			currentCustomerId,
			showNotification,
		]
	);

	const loadVehicleData = useCallback(
		(vehicle_no) => {
			console.log(vehicle_no);
			console.log(lastLoadedVehicleNo.current);

			// Skip if this vehicle is already loaded
			if (lastLoadedVehicleNo.current === vehicle_no) {
				return;
			}

			typewatch(
				async () => {
					try {
						const response = await axios.get(BASE_URL + "/vehicles/getOrCreateByVehicleNo", {
							params: {
								vehicle_no,
								kilometer: vehicleKilometer,
								last_avg_oil_km: vehicleAvgOilKm,
								last_avg_km_day: vehicleAvgKmDay,
								last_next_visit: vehicleNextVisit,
								last_customer_id: currentCustomerId,
							},
							headers: {
								Accept: "application/json",
							},
						});
						console.log("Load Vehicle Response:", response.data);
						if (response.data.success && response.data.vehicle) {
							const vehicle = response.data.vehicle;
							console.log("Vehicle Data:", vehicle);
							// Track this vehicle as loaded
							lastLoadedVehicleNo.current = vehicle.vehicle_no;

							setVehicleKilometer(vehicle.kilometer || "");
							setVehicleAvgOilKm(vehicle.last_avg_oil_km || "");
							setVehicleAvgKmDay(vehicle.last_avg_km_day || "");
							setVehicleNextVisit(vehicle.last_next_visit || "");

							// Only set customer if no customer is currently selected (only set on first load)
							if (vehicle.last_customer_id && (currentCustomerId == -1 || currentCustomerId == null)) {
								setCurrentCustomerId(vehicle.last_customer_id);
							}

							showNotification(
								response.data.created
									? `New vehicle created: ${vehicle.vehicle_no}`
									: "Vehicle data loaded successfully",
								"success"
							);
						} else {
							setVehicleKilometer("");
							setVehicleAvgOilKm("");
							setVehicleAvgKmDay("");
							setVehicleNextVisit("");
							setCalculatedAvgPerDay("");
							showNotification(response.data.message || "Error processing vehicle", "warning");
						}
					} catch (error) {
						showNotification("Error loading vehicle data", "danger");
					}
				},
				300,
				"loadVehicle"
			);
		},
		[
			vehicleKilometer,
			vehicleAvgOilKm,
			vehicleAvgKmDay,
			vehicleNextVisit,
			currentCustomerId,
			typewatch,
			showNotification,
		]
	);

	useEffect(() => {
		if (!phoneNumber) return;
		typewatch(
			async () => {
				try {
					const response = await axios.get(BASE_URL + "/customers/byPhoneNumberOrCreateCustomer", {
						params: { phone_number: phoneNumber, customer_name: customerName },
					});

					if (response.data.success && response.data.customer) {
						const customer = response.data.customer;

						showNotification(
							response.data.created
								? `New customer created and selected: ${customer.full_name}`
								: `Customer found and selected: ${customer.full_name}`,
							"success"
						);

						// Update state - useEffect will handle loading sales history and refreshing cart
						if (customer.person_id != currentCustomerId) {
							setCurrentCustomerId(customer.person_id);
						}
					} else {
						showNotification(response.data.message || "Error processing customer", "danger");
					}
				} catch (error) {
					showNotification("Error searching for customer", "danger");
				}
			},
			200,
			"loadCustomer"
		);
	}, [phoneNumber, customerName, showNotification, typewatch]);

	const loadCustomerSalesHistory = useCallback(
		async (customer_id) => {
			if (!customer_id) return;

			setIsLoadingHistory(true);
			try {
				const response = await axios.get(BASE_URL + "/sales/customerSalesHistory", {
					params: { customer_id },
				});

				if (response.data.success && response.data.sales) {
					setCustomerSalesHistory(response.data.sales);
				} else {
					setCustomerSalesHistory([]);
				}
			} catch (error) {
				showNotification("Error loading customer sales history", "danger");
				setCustomerSalesHistory([]);
			} finally {
				setIsLoadingHistory(false);
			}
		},
		[showNotification]
	);

	const refreshCartData = useCallback(async () => {
		try {
			// Get sale_id from URL params to load cart for specific sale
			const urlParams = new URLSearchParams(window.location.search);
			const saleId = urlParams.get("sale_id");

			const response = await axios.get(`${BASE_URL}/${controller_name}/cart`, {
				params: { sale_id: saleId },
			});
			if (response.data && response.data.cart) {
				console.log("Loaded cart data:", response.data.cart);
				setCartItems(response.data.cart);
			}
		} catch (err) {
			console.error("Error refreshing cart data:", err);
		}
	}, [controller_name, BASE_URL]);

	const refreshCustomerData = useCallback(async () => {
		try {
			// Note: controller method is getCurrentCustomer() which maps to the
			// 'currentCustomer' route. Use lowercase initial path segment to match
			// how other GET handlers are exposed (e.g. /sales/cart).
			const response = await axios.get(`${BASE_URL}/${controller_name}/currentCustomer`);
			if (response.data && response.data.customer) {
				const cust = response.data.customer;
				setCurrentCustomerId(cust.person_id);
				setCustomerName(cust.full_name || `${cust.first_name} ${cust.last_name}`);
				setPhoneNumber(cust.phone_number || "");

				// If backend returned vehicle info, pre-fill vehicle fields as well
				if (response.data.vehicle) {
					const v = response.data.vehicle;
					// Only populate vehicle fields if a vehicle isn't already selected in the UI.
					if (!vehicleNo) {
						setVehicleNo(v.vehicle_no || "");
						setVehicleKilometer(v.kilometer || "");
						setVehicleAvgOilKm(v.last_avg_oil_km || "");
						setVehicleAvgKmDay(v.last_avg_km_day || "");
						setVehicleNextVisit(v.last_next_visit || "");
					}
				}

				loadCustomerSalesHistory(cust.person_id);
			}
		} catch (err) {
			console.error("Error refreshing customer data:", err);
		}
	}, [controller_name, loadCustomerSalesHistory, vehicleNo]);

	const searchItems = useCallback(
		async (term) => {
			try {
				const response = await axios.get(`${BASE_URL}/${controller_name}/itemSearch`, {
					params: { term },
				});
				return response.data;
			} catch (error) {
				return [];
			}
		},
		[controller_name]
	);

	// Event Handlers
	const handleItemSelect = useCallback((item) => {
		// Accept item object with possible id, label and pricing info
		if (item && item.id) setItemId(item.id);
		setItemSearch(item.label);
		setSelectedItemRaw(item.raw ?? item);
		setBasePrice(parseFloat(item.price) || 0);
		setBaseQuantity(parseFloat(item.single_unit_quantity) || 1);
		setPrice(item.price || "0.00");
		setQuantity(item.single_unit_quantity || "1");
		setUnit(item.pack_name || "pcs");
	}, []);

	const handleQuantityChange = useCallback(
		(newQuantity) => {
			setQuantity(newQuantity);
			const qty = parseFloat(newQuantity) || 1;
			if (baseQuantity > 0) {
				const newPrice = (basePrice / baseQuantity) * qty;
				setPrice(newPrice.toFixed(2));
			}
		},
		[basePrice, baseQuantity]
	);

	// Recompute price when unit changes: if the selected unit is the pack
	// (pack_name/unit_name) then use the pack price, otherwise use the
	// per-single-unit price derived from basePrice/baseQuantity.
	useEffect(() => {
		const qty = parseFloat(quantity) || 1;
		const bPrice = parseFloat(basePrice) || 0;
		const bQty = parseFloat(baseQuantity) || 1;

		// price per single unit
		const perSingle = bQty > 0 ? bPrice / bQty : bPrice;

		let perSelectedUnit = perSingle;
		if (selectedItemRaw) {
			const packName = selectedItemRaw.pack_name ?? selectedItemRaw.unit_name ?? "";
			if (unit && packName && unit === packName) {
				// use pack price as the price for the selected unit
				perSelectedUnit = bPrice;
			}
		}

		const total = perSelectedUnit * qty;
		setPrice(total.toFixed(2));
	}, [unit, basePrice, baseQuantity, quantity, selectedItemRaw]);

	const handleVehicleNoChange = useCallback(
		(value) => {
			setVehicleNo(value);
			if (value.length >= 2) {
				loadVehicleData(value);
			}
		},
		[loadVehicleData]
	);

	// Initialize Select2 autocomplete for vehicle number (uses global jQuery/select2 if available)
	useEffect(() => {
		const el = vehicleInputRef.current;
		const $ = window.jQuery || window.$;

		if (!el || !($ && $.fn && $.fn.select2)) {
			// select2 not available, do nothing
			return;
		}

		const ajaxUrl = `${BASE_URL}/vehicles/suggest`;

		$(el).select2({
			placeholder: "Vehicle No",
			tags: true,
			minimumInputLength: 0,
			allowClear: true,
			width: "100%",
			createTag: function (params) {
				const term = params.term.trim();
				if (term === "") {
					return null;
				}
				return {
					id: term,
					text: term,
					newTag: true,
				};
			},
			ajax: {
				url: ajaxUrl,
				dataType: "json",
				delay: 300,
				data: function (params) {
					return { term: params.term || "" };
				},
				processResults: function (data) {
					// data may be an array of strings or objects; normalize to { id, text }
					const results = (data || []).map((item) => {
						if (item && typeof item === "object") {
							// Common shapes: { value, label } or { id, text }
							return {
								id: item.value ?? item.id ?? item.label,
								text: item.label ?? item.text ?? item.value ?? item.id,
							};
						}
						return { id: item, text: item };
					});
					return { results };
				},
			},
		});

		const onSelectWrapped = function (e) {
			const data = e.params && e.params.data;
			const id = data && (data.id ?? data.text);
			const text = data && (data.text ?? data.id ?? data.text);
			console.log("Select2 Vehicle Selected:", data);
			if (id) {
				try {
					// Ensure the select contains an option for the selected value so Select2 displays it
					const option = new Option(text || id, id, true, true);
					$(el).append(option).trigger("change");
				} catch (err) {
					// fallback: set value directly
					$(el).val(id).trigger("change");
					console.warn("Select2 append option fallback:", err);
				}

				setVehicleNo(id);
				// give select2 change cycle a tick then load vehicle data
				setTimeout(() => loadVehicleData(id), 0);
			}
		};

		$(el).on("select2:select", onSelectWrapped);

		// If the user types a value and closes the dropdown or presses Enter without selecting,
		// create/select an option for that typed value and call loadVehicleData.
		// $(el).on("select2:close", function () {
		// 	const val = $(el).val();
		// 	if (val && val.length >= 2) {
		// 		try {
		// 			const option = new Option(val, val, true, true);
		// 			$(el).append(option).trigger("change");
		// 		} catch (err) {
		// 			$(el).val(val).trigger("change");
		// 			console.warn("Select2 append option fallback:", err);
		// 		}
		// 		setVehicleNo(val);
		// 		setTimeout(() => loadVehicleData(val), 0);
		// 	}
		// });

		// Handle Enter keypress when select2 has focus
		$(el).on("keypress", function (e) {
			if (e.which === 13) {
				e.preventDefault();
				const val = $(el).val();
				if (val && val.length >= 2) {
					try {
						const option = new Option(val, val, true, true);
						$(el).append(option).trigger("change");
					} catch (err) {
						$(el).val(val).trigger("change");
						console.warn("Select2 append option fallback:", err);
					}
					setVehicleNo(val);
					setTimeout(() => loadVehicleData(val), 0);
				}
			}
		});

		return () => {
			try {
				$(el).off("select2:select", onSelectWrapped);
				$(el).off("select2:close");
				$(el).off("keypress");
				$(el).select2("destroy");
			} catch (err) {
				// ignore destroy errors but log for debugging
				console.warn("Select2 destroy error:", err);
			}
		};
	}, [BASE_URL, loadVehicleData]);

	// Initialize Select2 for Customer Name
	useEffect(() => {
		const el = customerNameRef.current;
		const $ = window.jQuery || window.$;

		if (!el || !($ && $.fn && $.fn.select2)) return;

		const ajaxUrl = `${BASE_URL}/customers/suggest`;

		$(el).select2({
			placeholder: "Customer Name",
			tags: true,
			minimumInputLength: 0,
			allowClear: true,
			width: "100%",
			createTag: function (params) {
				const term = params.term.trim();
				if (term === "") return null;
				return { id: term, text: term, newTag: true };
			},
			ajax: {
				url: ajaxUrl,
				dataType: "json",
				delay: 800,
				data: function (params) {
					return { term: params.term || "" };
				},
				processResults: function (data) {
					const results = (data || []).map((item) => {
						if (item && typeof item === "object") {
							return {
								id: item.value ?? item.id ?? item.label,
								text: item.label ?? item.text ?? item.value ?? item.id,
							};
						}
						return { id: item, text: item };
					});
					return { results };
				},
			},
		});

		const onSelect = function (e) {
			const data = e.params && e.params.data;
			const id = data && (data.id ?? data.text);
			const text = data && (data.text ?? data.id ?? data.text);
			if (id) {
				try {
					const option = new Option(text || id, id, true, true);
					$(el).append(option).trigger("change");
				} catch (err) {
					$(el).val(id).trigger("change");
					console.warn("Select2 append option fallback (customer):", err);
				}

				setCustomerName(text || id);
				// If id looks numeric (existing customer), load full customer by id
				if (!isNaN(Number(id))) {
					setTimeout(() => setCurrentCustomerId(id), 0);
				} else {
					// If user typed a new name and a phone number exists, attempt to create/load by phone using current name
					// if (phoneNumber && phoneNumber.length >= 3) {
					// 	setTimeout(() => loadCustomerByPhone(phoneNumber), 0);
					// }
				}
			}
		};

		$(el).on("select2:select", onSelect);

		$(el).on("select2:close", function () {
			const val = $(el).val();
			if (val && val.length >= 1) {
				// treat as typed name if not numeric id
				const name = Array.isArray(val) ? val[val.length - 1] : val;
				setCustomerName(name);
				// if (phoneNumber && phoneNumber.length >= 3) {
				// 	setTimeout(() => loadCustomerByPhone(phoneNumber), 0);
				// }
			}
		});

		$(el).on("keypress", function (e) {
			if (e.which === 13) {
				e.preventDefault();
				const val = $(el).val();
				const name = Array.isArray(val) ? val[val.length - 1] : val;
				if (name && name.length >= 1) {
					setCustomerName(name);
					// if (phoneNumber && phoneNumber.length >= 3) {
					// 	setTimeout(() => loadCustomerByPhone(phoneNumber), 0);
					// }
				}
			}
		});

		return () => {
			try {
				$(el).off("select2:select", onSelect);
				$(el).off("select2:close");
				$(el).off("keypress");
				$(el).select2("destroy");
			} catch (err) {
				console.warn("Select2 destroy error (customer):", err);
			}
		};
	}, [BASE_URL, phoneNumber]);

	// Initialize Select2 for Phone Number (tags + create-or-load)
	useEffect(() => {
		const el = phoneInputRef.current;
		const $ = window.jQuery || window.$;
		if (!el || !($ && $.fn && $.fn.select2)) return;

		$(el).select2({
			placeholder: "Phone Number",
			tags: true,
			minimumInputLength: 0,
			allowClear: true,
			width: "100%",
		});

		const onSelect = function (e) {
			const data = e.params && e.params.data;
			const id = data && (data.id ?? data.text);
			if (id) {
				try {
					const option = new Option(id, id, true, true);
					$(el).append(option).trigger("change");
				} catch (err) {
					$(el).val(id).trigger("change");
					console.warn("Select2 append option fallback (phone):", err);
				}
				setPhoneNumber(id);
				// setTimeout(() => loadCustomerByPhone(id), 0);
			}
		};

		$(el).on("select2:select", onSelect);

		$(el).on("select2:close", function () {
			const val = $(el).val();
			if (val && val.length >= 3) {
				const phone = Array.isArray(val) ? val[val.length - 1] : val;
				try {
					const option = new Option(phone, phone, true, true);
					$(el).append(option).trigger("change");
				} catch (err) {
					$(el).val(phone).trigger("change");
					console.warn("Select2 append option fallback (phone close):", err);
				}
				setPhoneNumber(phone);
				// setTimeout(() => loadCustomerByPhone(phone), 0);
			}
		});

		$(el).on("keypress", function (e) {
			if (e.which === 13) {
				e.preventDefault();
				const val = $(el).val();
				const phone = Array.isArray(val) ? val[val.length - 1] : val;
				if (phone && phone.length >= 3) {
					try {
						const option = new Option(phone, phone, true, true);
						$(el).append(option).trigger("change");
					} catch (err) {
						$(el).val(phone).trigger("change");
						console.warn("Select2 append option fallback (phone enter):", err);
					}
					setPhoneNumber(phone);
					// setTimeout(() => loadCustomerByPhone(phone), 0);
				}
			}
		});

		return () => {
			try {
				$(el).off("select2:select", onSelect);
				$(el).off("select2:close");
				$(el).off("keypress");
				$(el).select2("destroy");
			} catch (err) {
				console.warn("Select2 destroy error (phone):", err);
			}
		};
	}, [phoneNumber]);

	// Update document title when Vehicle No or Customer Name changes
	useEffect(() => {
		let title = "Zafcom Point of Sale";
		
		if (vehicleNo || customerName) {
			const parts = [];
			if (vehicleNo) parts.push(vehicleNo);
			if (customerName) parts.push(customerName);
			title = `${parts.join(" | ")} - Zafcom Point of Sale`;
		}
		
		document.title = title;
	}, [vehicleNo, customerName]);

	// Keep select2 visual value in sync when vehicleNo state changes (if select2 is present)
	useEffect(() => {
		const el = vehicleInputRef.current;
		const $ = window.jQuery || window.$;
		if (!el || !($ && $.fn && $.fn.select2)) return;
		try {
			$(el).val(vehicleNo).trigger("change.select2");
		} catch (err) {
			// Log sync errors for debugging
			console.warn("Select2 sync/trigger error (vehicle):", err);
		}
	}, [vehicleNo]);

	// Keep select2 visual value in sync when customerName changes
	useEffect(() => {
		const el = customerNameRef.current;
		const $ = window.jQuery || window.$;
		if (!el || !($ && $.fn && $.fn.select2)) return;
		try {
			$(el).val(customerName).trigger("change.select2");
		} catch (err) {
			console.warn("Select2 sync/trigger error (customer):", err);
		}
	}, [customerName]);

	// Keep select2 visual value in sync when phoneNumber changes
	useEffect(() => {
		const el = phoneInputRef.current;
		const $ = window.jQuery || window.$;
		if (!el || !($ && $.fn && $.fn.select2)) return;
		try {
			$(el).val(phoneNumber).trigger("change.select2");
		} catch (err) {
			console.warn("Select2 sync/trigger error (phone):", err);
		}
	}, [phoneNumber]);

	const handlePhoneNumberChange = useCallback((value) => {
		setPhoneNumber(value);
	}, []);

	// Edit item handlers - must be defined before handleAddItem since handleAddItem depends on them
	const cancelEditItem = useCallback(() => {
		setIsEditingItem(false);
		setCurrentEditingItem(null);
		setQuantity("1");
		setUnit("pcs");
		setPrice("0.00");
		setDiscount("0.00");
		setDiscountToggle(false);
		setItemSearch("");
		setItemId("");
	}, []);

	const handleSaveEditItem = useCallback(
		async (e) => {
			e.preventDefault();
			if (!currentEditingItem) return;

			const lineId = currentEditingItem.line ?? currentEditingItem.item_id ?? currentEditingItem.rowid ?? 0;
			const params = new URLSearchParams();
			params.append("line_id", lineId);
			params.append("sale_id", new URLSearchParams(window.location.search).get("sale_id") || "");
			params.append("quantity", quantity);
			params.append("unit", unit);
			params.append("price", price);
			params.append("discount", discount);
			params.append("discount_toggle", discountToggle ? "1" : "0");

			try {
				await axios.post(`${BASE_URL}/${controller_name}/updateItem`, params.toString(), {
					headers: { "Content-Type": "application/x-www-form-urlencoded" },
				});
				cancelEditItem();
				await refreshCartData();
				showNotification("Item updated successfully", "success");
			} catch (err) {
				console.error("Error updating item:", err);
				showNotification("Error updating item", "danger");
			}
		},
		[
			currentEditingItem,
			quantity,
			unit,
			price,
			discount,
			discountToggle,
			controller_name,
			refreshCartData,
			showNotification,
			cancelEditItem,
			BASE_URL,
		]
	);

	const handleAddItem = useCallback(
		async (e) => {
			e.preventDefault();

			// If editing, delegate to edit handler
			if (isEditingItem && currentEditingItem) {
				await handleSaveEditItem(e);
				return;
			}

			// Send as application/x-www-form-urlencoded so CodeIgniter request->getPost() can read values
			const params = new URLSearchParams();
			params.append("sale_id", new URLSearchParams(window.location.search).get("sale_id") || "");
			params.append("item", itemId || itemSearch);
			params.append("quantity", quantity);
			params.append("unit", unit);
			params.append("price", price);
			params.append("discount", discount);
			params.append("discount_toggle", discountToggle ? "1" : "0");
			// Include single unit metadata from selected item so backend can
			// convert total -> unit price reliably if needed.
			if (selectedItemRaw) {
				params.append("single_unit_quantity", selectedItemRaw.single_unit_quantity ?? "");
				params.append("pack_name", selectedItemRaw.pack_name ?? selectedItemRaw.unit_name ?? "");
			}

			try {
				await axios.post(BASE_URL + `/${controller_name}/add`, params.toString(), {
					headers: { "Content-Type": "application/x-www-form-urlencoded" },
				});
				// Reset form
				setItemSearch("");
				setQuantity("1");
				setPrice("0.00");
				setDiscount("0.00");
				setDiscountToggle(false);

				// Refresh cart data instead of reloading page
				refreshCartData();
				showNotification("Item added to cart successfully", "success");
			} catch (err) {
				console.error("Error adding item to cart:", err);
				showNotification("Error adding item to cart", "danger");
			}
		},
		[
			itemSearch,
			itemId,
			quantity,
			unit,
			price,
			discount,
			discountToggle,
			selectedItemRaw,
			controller_name,
			showNotification,
			refreshCartData,
			isEditingItem,
			currentEditingItem,
			handleSaveEditItem,
		]
	);

	const handleRemoveItem = useCallback(
		async (id) => {
			// id can be a cart line index or an item_id depending on backend shape
			if (id === undefined || id === null || id === "") {
				showNotification("No item identifier provided for removal", "warning");
				return;
			}

			try {
				// Use encodeURIComponent to guard against unexpected characters
				const saleId = new URLSearchParams(window.location.search).get("sale_id");
				const deleteUrl = saleId 
					? `${BASE_URL}/${controller_name}/deleteItem/${encodeURIComponent(id)}?sale_id=${encodeURIComponent(saleId)}`
					: `${BASE_URL}/${controller_name}/deleteItem/${encodeURIComponent(id)}`;
				await axios.get(deleteUrl);
				await refreshCartData();
				showNotification("Item removed from cart", "success");
			} catch (error) {
				console.error("Error removing item from cart:", error);
				showNotification("Error removing item from cart", "danger");
			}
		},
		[controller_name, refreshCartData, showNotification]
	);

	const submitCompleteForm = useCallback(() => {
		// Create and submit a POST form to mirror the original register_bak behavior
		try {
			const urlParams = new URLSearchParams(window.location.search);
			const saleId = urlParams.get("sale_id");

			const form = document.createElement("form");
			form.method = "POST";
			// Use an absolute path so browser submits to the same host/port
			form.action = BASE_URL + `/${controller_name}/complete`;

			// Add sale_id as hidden input if available
			if (saleId) {
				const saleIdInput = document.createElement("input");
				saleIdInput.type = "hidden";
				saleIdInput.name = "sale_id";
				saleIdInput.value = saleId;
				form.appendChild(saleIdInput);
			}

			form.style.display = "none";
			document.body.appendChild(form);
			form.submit();
		} catch {
			// Show a toast notification on failure instead of redirecting
			showNotification("Could not complete sale. Please try again.", "danger");
		}
	}, [controller_name, BASE_URL, showNotification]);

	const handleFinishSale = useCallback(() => {
		// Save vehicle data (if present) then submit the complete form.
		// This mirrors register_bak: if vehicle_no present, attempt AJAX save
		// then complete sale; otherwise complete sale immediately.
		saveVehicleData(() => {
			submitCompleteForm();
		});
	}, [saveVehicleData, submitCompleteForm]);

	// Payment inputs state: store amounts keyed by payment option key
	const [paymentInputs, setPaymentInputs] = useState(() => {
		const init = {};
		try {
			if (payment_options && typeof payment_options === "object") {
				Object.keys(payment_options).forEach((k) => {
					init[k] = "";
				});
			}
		} catch {
			// ignore
		}
		return init;
	});

	const calculateSaleTotal = useCallback(() => {
		const arr = Array.isArray(cartItems) ? cartItems : Object.values(cartItems || {});
		return arr.reduce(
			(acc, it) =>
				acc +
				(it.discounted_total != null
					? parseFloat(it.discounted_total)
					: (parseFloat(it.price ?? it.item_unit_price ?? it.unit_price ?? 0) || 0) *
							(parseFloat(it.quantity ?? it.quantity_purchased ?? 0) || 0) -
					  (parseFloat(it.discount ?? 0) || 0)),
			0
		);
	}, [cartItems]);

	const handlePaymentInputChange = useCallback(
		(key, value) => {
			setPaymentInputs((prev) => {
				const updated = { ...prev, [key]: value };

				// Auto-calculate cash payment if this is not the cash field
				// Assume "cash" is the key for cash payment. If not found, skip auto-calculation.
				const cashKey = Object.keys(payment_options || {}).find((k) => k.toLowerCase() === "cash");

				if (cashKey && key !== cashKey) {
					// Calculate total from other payment methods (excluding cash)
					const otherPaymentTotal = Object.entries(updated)
						.filter(([k]) => k !== cashKey)
						.reduce((acc, [, v]) => acc + (parseFloat(String(v).replace(",", ".")) || 0), 0);

					// Get the sale total
					const saleTotal = calculateSaleTotal();

					// Auto-fill cash: total sale amount minus other payments
					const cashAmount = Math.max(0, saleTotal - otherPaymentTotal);
					updated[cashKey] = cashAmount > 0 ? cashAmount.toFixed(2) : "";
				}

				return updated;
			});
		},
		[payment_options, calculateSaleTotal]
	);

	const handleEditItem = useCallback((item) => {
		setCurrentEditingItem(item);
		setIsEditingItem(true);
		setQuantity(String(item.quantity ?? item.quantity_purchased ?? "1"));
		setUnit(item.unit ?? "pcs");
		setPrice(String(item.price ?? item.item_unit_price ?? item.unit_price ?? "0.00"));
		setDiscount(String(item.discount ?? "0.00"));
		setDiscountToggle(item.discount_type === "fixed" || false);
	}, []);

	const combinedPaymentTotal = useMemo(() => {
		return Object.values(paymentInputs).reduce((acc, v) => acc + (parseFloat(String(v).replace(",", ".")) || 0), 0);
	}, [paymentInputs]);

	// Auto-load cash total on page load and when sale total changes
	useEffect(() => {
		setPaymentInputs((prev) => {
			const updated = { ...prev };
			const cashKey = Object.keys(payment_options || {}).find((k) => k.toLowerCase() === "cash");

			if (cashKey) {
				// Calculate total from other payment methods (excluding cash)
				const otherPaymentTotal = Object.entries(updated)
					.filter(([k]) => k !== cashKey)
					.reduce((acc, [, v]) => acc + (parseFloat(String(v).replace(",", ".")) || 0), 0);

				// Get the sale total
				const saleTotal = calculateSaleTotal();

				// Auto-fill cash: total sale amount minus other payments
				const cashAmount = Math.max(0, saleTotal - otherPaymentTotal);
				updated[cashKey] = cashAmount > 0 ? cashAmount.toFixed(2) : "";
			}

			return updated;
		});
	}, [calculateSaleTotal, payment_options]);

	// Effects
	useEffect(() => {
		calculateAveragePerDay();
	}, [calculateAveragePerDay]);

	useEffect(() => {
		console.log("Loading customer by ID:", currentCustomerId);

		// Skip if we're loading an existing sale from URL
		// const urlParams = new URLSearchParams(window.location.search);
		// const urlSaleId = urlParams.get("sale_id");
		// if (urlSaleId) {
		// 	console.log("Skipping customer refresh - loading existing sale from URL");
		// 	return;
		// }

		if (!currentCustomerId) return;

		axios
			.get(BASE_URL + "/customers/customerById", {
				params: { customer_id: currentCustomerId },
			})
			.then(async (response) => {
				console.log("Customer by ID response:", response.data);
				if (response.data.success && response.data.customer) {
					const customer = response.data.customer;

					setCustomerName(`${customer.first_name} ${customer.last_name}`);
					setPhoneNumber(customer.phone_number || "");

					loadCustomerSalesHistory(currentCustomerId);
					refreshCartData();
				}
			});
	}, [currentCustomerId, loadCustomerSalesHistory, refreshCartData]);

	// No automatic focus: avoid stealing focus from the user.

	// Initialize sale before anything else
	useEffect(() => {
		if (!initialCustomerLoaded.current) {
			initialCustomerLoaded.current = true;

			// Initialize sale first - get sale_id from URL params
			const urlParams = new URLSearchParams(window.location.search);
			const urlSaleId = urlParams.get("sale_id");

			axios
				.get(`${BASE_URL}/${controller_name}/initSale`, {
					params: { sale_id: urlSaleId },
				})
				.then((response) => {
					console.log("initSale response:", response.data);
					if (response.data.success) {
						// Update URL with the sale_id (either existing or newly created)
						const newUrl = new URL(window.location);
						newUrl.searchParams.set("sale_id", response.data.sale_id);
						window.history.replaceState({}, "", newUrl);

						// Load sale data
						if (response.data.sale) {
							const s = response.data.sale;
							console.log("Loading sale data:", s);

							// Load mechanic name and comment from sale record
							setMechanicName(s.mechanic_name || "");
							setComment(s.comment || "");

							// Load customer if assigned
							if (s.customer_id && s.customer_id !== -1) {
								console.log("Setting customer ID:", s.customer_id);
								setCurrentCustomerId(s.customer_id);

								// Get customer name from multiple possible sources
								if (s.customer_name) {
									console.log("Setting customer name from sale:", s.customer_name);
									setCustomerName(s.customer_name);
								}

								// Get phone number from sale object or fallback to nested customer object
								if (s.phone_number) {
									console.log("Setting phone from sale:", s.phone_number);
									setPhoneNumber(s.phone_number);
								} else if (response.data.customer && response.data.customer.phone_number) {
									console.log(
										"Setting phone from customer object:",
										response.data.customer.phone_number
									);
									setPhoneNumber(response.data.customer.phone_number);
								}
							}

							// Load vehicle data from sale object if vehicle_id is assigned
							if (s.vehicle_id && s.vehicle_id !== -1) {
								console.log("Loading vehicle data from sale object:", {
									vehicle_id: s.vehicle_id,
									vehicle_kilometer: s.vehicle_kilometer,
									vehicle_avg_oil_km: s.vehicle_avg_oil_km,
									vehicle_avg_km_day: s.vehicle_avg_km_day,
								});
								setVehicleKilometer(s.vehicle_kilometer || 0);
								setVehicleAvgOilKm(s.vehicle_avg_oil_km || 0);
								setVehicleAvgKmDay(s.vehicle_avg_km_day || 0);
							}
						}

						// Also load vehicle data if returned in response for vehicle_no and last_next_visit
						if (
							response.data.vehicle &&
							typeof response.data.vehicle === "object" &&
							Object.keys(response.data.vehicle).length > 0
						) {
							console.log("Loading vehicle details:", response.data.vehicle);
							const v = response.data.vehicle;
							setVehicleNo(v.vehicle_no || "");
							setVehicleNextVisit(v.last_next_visit || "");
							if (lastLoadedVehicleNo) {
								lastLoadedVehicleNo.current = v.vehicle_no;
							}
						}

						// Load cart items if returned in response
						if (
							response.data.cart_items &&
							Array.isArray(response.data.cart_items) &&
							response.data.cart_items.length > 0
						) {
							console.log("Loading cart items:", response.data.cart_items);
							setCartItems(response.data.cart_items);
						}

						// Load payment methods/amounts if returned in response
						if (
							response.data.payments &&
							Array.isArray(response.data.payments) &&
							response.data.payments.length > 0
						) {
							console.log("Loading payments:", response.data.payments);
							const paymentsMap = {};
							response.data.payments.forEach((payment) => {
								// Map payment_type to payment_amount
								paymentsMap[payment.payment_type] = payment.payment_amount;
							});
							console.log("Payments map:", paymentsMap);
							setPaymentInputs(paymentsMap);
						}
					}
				})
				.catch((err) => {
					console.error("Error initializing sale:", err);
				});
		}
	}, [controller_name, BASE_URL]);

	// Only run initial customer/vehicle load once. This effect depends on
	// several callbacks (loadVehicleData, loadCustomerById, refreshCustomerData)
	// but should not re-focus the item input when those change.
	useEffect(() => {
		if (initialCustomerLoaded.current) {
			// Check if we have a sale_id in URL - if so, skip this effect
			// because the initSale effect already loaded all the data
			const urlParams = new URLSearchParams(window.location.search);
			const urlSaleId = urlParams.get("sale_id");

			// Only load defaults if we don't have a URL sale_id
			if (!urlSaleId) {
				// If server provided a selected vehicle (via window.salesRegisterProps),
				// prefer loading that vehicle first so the UI reflects the selected sale vehicle.
				if (selected_vehicle_no) {
					// Best-effort: initialize vehicleNo from server-provided selected vehicle
					setVehicleNo(selected_vehicle_no);
					// load vehicle info by its number to populate vehicle fields
					try {
						loadVehicleData(selected_vehicle_no);
					} catch {
						// ignore load errors here - vehicle loading is best-effort
					}
				}

				if (customer_id) {
					setCurrentCustomerId(customer_id);
				} else {
					refreshCustomerData();
				}
			}
		}
	}, [refreshCustomerData, loadVehicleData, selected_vehicle_no, selected_vehicle_id, customer_id]);

	// Auto-save customer ID to database when it changes
	useEffect(() => {
		if (!currentCustomerId || currentCustomerId === -1) return;

		const urlParams = new URLSearchParams(window.location.search);
		const saleId = urlParams.get("sale_id");
		if (!saleId) return;

		const timer = setTimeout(() => {
			const params = new URLSearchParams();
			params.append("sale_id", saleId);
			params.append("customer_id", currentCustomerId);

			axios
				.post(`${BASE_URL}/${controller_name}/saveSaleData`, params.toString(), {
					headers: { "Content-Type": "application/x-www-form-urlencoded" },
				})
				.catch((err) => console.error("Error saving sale data:", err));
		}, 1000); // Debounce by 1 second

		return () => clearTimeout(timer);
	}, [currentCustomerId, controller_name, BASE_URL]);

	// Auto-save vehicle data and comments to database when vehicle info changes
	useEffect(() => {
		if (!vehicleNo) return;

		const urlParams = new URLSearchParams(window.location.search);
		const saleId = urlParams.get("sale_id");
		if (!saleId) return;

		const timer = setTimeout(() => {
			const params = new URLSearchParams();
			params.append("sale_id", saleId);
			params.append("vehicle_no", vehicleNo);
			params.append("vehicle_kilometer", vehicleKilometer || 0);
			params.append("vehicle_avg_oil_km", vehicleAvgOilKm || 0);
			params.append("vehicle_avg_km_day", vehicleAvgKmDay || 0);
			params.append("vehicle_next_visit", vehicleNextVisit || "");
			params.append("mechanic_name", mechanicName || "");
			params.append("comment", comment || "");
			params.append("customer_id", currentCustomerId || -1);

			axios
				.post(`${BASE_URL}/${controller_name}/saveSaleData`, params.toString(), {
					headers: { "Content-Type": "application/x-www-form-urlencoded" },
				})
				.catch((err) => console.error("Error saving vehicle data:", err));
		}, 1000); // Debounce by 1 second

		return () => clearTimeout(timer);
	}, [
		vehicleNo,
		vehicleKilometer,
		vehicleAvgOilKm,
		vehicleAvgKmDay,
		vehicleNextVisit,
		mechanicName,
		comment,
		currentCustomerId,
		controller_name,
		BASE_URL,
	]);

	// Auto-save payment data to database when payment inputs change
	useEffect(() => {
		const urlParams = new URLSearchParams(window.location.search);
		const saleId = urlParams.get("sale_id");
		if (!saleId) return;

		// Check if any payment has been entered
		const hasPayment = Object.entries(paymentInputs).some(([, value]) => {
			return (parseFloat(String(value).replace(",", ".")) || 0) > 0;
		});

		if (!hasPayment) return;

		const timer = setTimeout(() => {
			// Save each payment to the backend
			Object.entries(paymentInputs).forEach(([paymentType, amount]) => {
				const parsedAmount = parseFloat(String(amount).replace(",", ".")) || 0;
				if (parsedAmount > 0) {
					const params = new URLSearchParams();
					params.append("sale_id", saleId);
					params.append("payment_type", paymentType);
					params.append("payment_amount", parsedAmount);

					axios
						.post(`${BASE_URL}/${controller_name}/saveSaleData`, params.toString(), {
							headers: { "Content-Type": "application/x-www-form-urlencoded" },
						})
						.catch((err) => console.error("Error saving payment data:", err));
				}
			});
		}, 1000); // Debounce by 1 second

		return () => clearTimeout(timer);
	}, [paymentInputs, controller_name, BASE_URL]);

	// When the vehicle selection changes, attempt to load the vehicle record
	// and auto-select the last customer associated with that vehicle (if any).
	// NOTE: This is now handled by loadVehicleData callback, so this effect is removed
	// to prevent duplicate API calls.

	// Plain React autocomplete for Product Name / Barcode (no Select2)
	const [itemSuggestions, setItemSuggestions] = useState([]);
	const [showItemSuggestions, setShowItemSuggestions] = useState(false);
	const [highlightedIndex, setHighlightedIndex] = useState(-1);

	useEffect(() => {
		// debounce search for itemSearch using existing typewatch util
		if (!itemSearch || itemSearch.length < 1) {
			setItemSuggestions([]);
			setShowItemSuggestions(false);
			setHighlightedIndex(-1);
			return;
		}

		typewatch(
			async () => {
				try {
					const results = await searchItems(itemSearch);
					const normalized = (results || []).map((item) => {
						const id =
							item.item_id ??
							item.id ??
							item.value ??
							item.label ??
							item.name ??
							item.code ??
							item.barcode ??
							item;
						const label = item.label ?? item.name ?? item.value ?? item.text ?? item.barcode ?? id;
						return { raw: item, id, label };
					});
					setItemSuggestions(normalized);
					setShowItemSuggestions(true);
					setHighlightedIndex(-1);
				} catch (err) {
					console.warn("Item search error:", err);
					setItemSuggestions([]);
					setShowItemSuggestions(false);
				}
			},
			250,
			"itemSearch"
		);
	}, [itemSearch, searchItems, typewatch]);

	// Handle keyboard navigation and selection
	const onItemInputKeyDown = useCallback(
		(e) => {
			if (!showItemSuggestions || itemSuggestions.length === 0) return;

			if (e.key === "ArrowDown") {
				e.preventDefault();
				setHighlightedIndex((idx) => Math.min(idx + 1, itemSuggestions.length - 1));
			} else if (e.key === "ArrowUp") {
				e.preventDefault();
				setHighlightedIndex((idx) => Math.max(idx - 1, 0));
			} else if (e.key === "Enter") {
				if (highlightedIndex >= 0 && highlightedIndex < itemSuggestions.length) {
					e.preventDefault();
					const sel = itemSuggestions[highlightedIndex];
					if (sel) {
						const payload = sel.raw || {};
						handleItemSelect({
							id: sel.id,
							label: sel.label,
							price: payload.price ?? payload.unit_price ?? payload.cost ?? "0.00",
							single_unit_quantity: payload.single_unit_quantity ?? 1,
							pack_name: payload.pack_name ?? payload.unit_name ?? "pcs",
						});
						setItemSearch(sel.label);
						setItemId(sel.id ?? "");
						setItemSuggestions([]);
						setShowItemSuggestions(false);
						setHighlightedIndex(-1);
					}
				}
			} else if (e.key === "Escape") {
				setShowItemSuggestions(false);
				setHighlightedIndex(-1);
			}
		},
		[showItemSuggestions, itemSuggestions, highlightedIndex, handleItemSelect]
	);

	const onSelectSuggestion = useCallback(
		(sel) => {
			if (!sel) return;
			const payload = sel.raw || {};
			handleItemSelect({
				id: sel.id,
				label: sel.label,
				price: payload.price ?? payload.unit_price ?? payload.cost ?? "0.00",
				single_unit_quantity: payload.single_unit_quantity ?? 1,
				pack_name: payload.pack_name ?? payload.unit_name ?? "pcs",
			});
			setSelectedItemRaw(payload);
			setItemSearch(sel.label);
			setItemId(sel.id ?? "");
			setItemSuggestions([]);
			setShowItemSuggestions(false);
			setHighlightedIndex(-1);
		},
		[handleItemSelect]
	);

	// Close suggestions when clicking outside
	useEffect(() => {
		const handler = (e) => {
			if (!itemInputRef.current) return;
			if (!itemInputRef.current.contains(e.target)) {
				setShowItemSuggestions(false);
			}
		};
		document.addEventListener("click", handler);
		return () => document.removeEventListener("click", handler);
	}, []);

	console.log("Render SalesRegister: ", cartItems);
	// Normalize cartItems to an array because the backend may return an
	// object keyed by line/index (e.g. { "1": { ... } }) rather than an
	// array. Using cartArray everywhere avoids `slice is not a function`.
	const cartArray = Array.isArray(cartItems) ? cartItems : Object.values(cartItems || {});
	// Component JSX
	return (
		<div className="sales-register">
			{/* Notifications */}
			<div
				className="notifications-container"
				style={{ position: "fixed", top: "20px", right: "20px", zIndex: 1000 }}
			>
				{notifications.map((notification) => (
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
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="required control-label" style={{ width: "100%" }}>
										Vehicle No
									</label>
									<select
										id="vehicle_no"
										ref={vehicleInputRef}
										className="form-control input-sm"
										value={vehicleNo}
										onChange={(e) => handleVehicleNoChange(e.target.value.toUpperCase())}
										tabIndex="1"
									>
										<option value="">{""}</option>
										{/* If vehicleNo is set but Select2/jQuery isn't present, render
                        an option so the select can display the current value. */}
										{vehicleNo && <option value={vehicleNo}>{vehicleNo}</option>}
									</select>
								</div>
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="required control-label" style={{ width: "100%" }}>
										Customer Name
									</label>
									<select
										id="customer_name"
										ref={customerNameRef}
										className="form-control input-sm"
										value={customerName}
										onChange={(e) => setCustomerName(e.target.value)}
										tabIndex="2"
									>
										<option value="">{""}</option>
										{/* Ensure the select can display the initial customer name when
                        Select2/jQuery isn't present by rendering a matching option */}
										{customerName && <option value={customerName}>{customerName}</option>}
									</select>
								</div>
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="required control-label" style={{ width: "100%" }}>
										Phone Number
									</label>
									<select
										id="phone_number"
										ref={phoneInputRef}
										className="form-control input-sm"
										value={phoneNumber}
										onChange={(e) => handlePhoneNumberChange(e.target.value)}
										tabIndex="3"
									>
										<option value="">{""}</option>
										{/* Add an option for the current phoneNumber so the select shows
                        the value when Select2 isn't available */}
										{phoneNumber && <option value={phoneNumber}>{phoneNumber}</option>}
									</select>
								</div>
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="control-label" style={{ width: "100%" }}>
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
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="control-label" style={{ width: "100%" }}>
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
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="control-label" style={{ width: "100%" }}>
										Avg KM/ Oil
									</label>
									<input
										type="number"
										className="form-control input-sm"
										value={vehicleAvgOilKm}
										onChange={(e) => setVehicleAvgOilKm(e.target.value)}
										tabIndex="6"
									/>
									{calculatedAvgPerDay && (
										<div style={{ color: "#2F4F4F", fontSize: "12px", marginTop: "5px" }}>
											{calculatedAvgPerDay}
										</div>
									)}
								</div>
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="control-label" style={{ width: "100%" }}>
										Next Visit
									</label>
									<input
										type="date"
										className="form-control input-sm"
										value={vehicleNextVisit}
										onChange={(e) => setVehicleNextVisit(e.target.value)}
										max={(() => {
											const maxDate = new Date();
											maxDate.setDate(maxDate.getDate() + 180);
											return maxDate.toISOString().split("T")[0];
										})()}
										tabIndex="7"
									/>
								</div>
							</div>
							<div className="col-sm-3">
								<div className="form-group form-group-sm">
									<label className="control-label" style={{ width: "100%" }}>
										Mechanic Name
									</label>
									<input
										type="text"
										className="form-control input-sm"
										value={mechanicName}
										onChange={(e) => setMechanicName(e.target.value)}
										placeholder="Enter mechanic name"
										tabIndex="8"
									/>
								</div>
							</div>
						</div>
						<div className="form-group form-group-sm">
							<label className="control-label" style={{ width: "100%" }}>
								Comments
							</label>
							<textarea
								className="form-control input-sm"
								rows="3"
								value={comment}
								onChange={(e) => setComment(e.target.value)}
								placeholder="Add any comments or notes"
								style={{ resize: "vertical" }}
							/>
						</div>
					</div>{" "}
					{/* Add Item Form */}
					<form onSubmit={handleAddItem} className="form-horizontal panel panel-default">
						<div
							className="panel-body row"
							style={{ display: "flex", justifyContent: "center", alignItems: "end" }}
						>
							{isEditingItem && currentEditingItem ? (
								// Edit mode
								<>
									<div className="col-sm-4" style={{ marginBottom: "1em" }}>
										<h5 style={{ color: "#0066cc" }}>
											Editing:
											<br />
											{currentEditingItem.name}
										</h5>
									</div>
									<div className="col-sm-2">
										<label className="control-label">Quantity</label>
										<div className="input-group">
											<input
												tabIndex="9"
												type="number"
												className="form-control input-sm"
												value={quantity}
												onChange={(e) => handleQuantityChange(e.target.value)}
												required
											/>
											<span className="input-group-addon" style={{ padding: 0 }}>
												<select
													tabIndex="10"
													className="form-control input-sm"
													value={unit}
													onChange={(e) => setUnit(e.target.value)}
													style={{ width: "38px", padding: 0, height: "33px", border: 0 }}
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
											tabIndex="11"
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
												tabIndex="12"
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
													tabIndex="13"
												/>
											</span>
										</div>
									</div>
									<div className="col-sm-2">
										<label className="control-label">&nbsp;</label>
										<button type="submit" className="btn btn-success btn-sm" tabIndex="14">
											<span className="glyphicon glyphicon-ok"></span> Save Changes
										</button>
									</div>
									<div className="col-sm-2">
										<label className="control-label">&nbsp;</label>
										<button
											type="button"
											className="btn btn-danger btn-sm"
											tabIndex="15"
											onClick={cancelEditItem}
										>
											<span className="glyphicon glyphicon-remove"></span> Cancel
										</button>
									</div>
								</>
							) : (
								// Add mode
								<>
									<div className="col-sm-4">
										<label className="control-label">Product Name / Barcode</label>
										<div ref={itemInputRef} style={{ position: "relative" }}>
											<input
												tabIndex="8"
												type="text"
												className="form-control input-sm"
												value={itemSearch}
												onChange={(e) => {
													setItemSearch(e.target.value);
													setItemId("");
												}}
												onKeyDown={onItemInputKeyDown}
												aria-autocomplete="list"
												aria-expanded={showItemSuggestions}
												placeholder="Type product name or barcode"
												required
											/>
										</div>
									</div>
									<div className="col-sm-2">
										<label className="control-label">Quantity</label>
										<div className="input-group">
											<input
												tabIndex="9"
												type="number"
												className="form-control input-sm"
												value={quantity}
												onChange={(e) => handleQuantityChange(e.target.value)}
												min="1"
												required
											/>
											<span className="input-group-addon" style={{ padding: 0 }}>
												<select
													tabIndex="10"
													className="form-control input-sm"
													value={unit}
													onChange={(e) => setUnit(e.target.value)}
													style={{ width: "45px", padding: 0, height: "33px", border: 0 }}
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
											tabIndex="11"
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
												tabIndex="12"
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
													tabIndex="13"
												/>
											</span>
										</div>
									</div>
									<div className="col-sm-2">
										<label className="control-label">&nbsp;</label>
										<button type="submit" className="btn btn-primary btn-sm" tabIndex="14">
											<span className="glyphicon glyphicon-plus"></span> Add
										</button>
									</div>
								</>
							)}
						</div>
						{showItemSuggestions && itemSuggestions && itemSuggestions.length > 0 && (
							<div
								className="autocomplete-suggestions"
								style={{
									position: "absolute",
									zIndex: 1050,
									left: 0,
									right: 0,
									maxHeight: "400px",
									overflowY: "auto",
									boxShadow: "0 2px 6px rgba(0,0,0,0.15)",
									backgroundColor: "#fff",
									border: "1px solid #ddd",
									borderRadius: "4px",
									width: "96%",
									margin: "0 16px",
								}}
							>
								<table
									className="table table-hover table-condensed"
									style={{ marginBottom: 0, fontSize: "11px" }}
								>
									<thead>
										<tr style={{ backgroundColor: "#f5f5f5" }}>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Product
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Category
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Brand
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Part No
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												OEM No
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Make
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Packing
											</th>
											<th style={{ padding: "4px 6px", fontWeight: "600", fontSize: "10px" }}>
												Weight
											</th>
											<th
												style={{
													padding: "4px 6px",
													fontWeight: "600",
													fontSize: "10px",
													textAlign: "right",
												}}
											>
												Price
											</th>
										</tr>
									</thead>
									<tbody>
										{itemSuggestions.map((sugg, idx) => {
											const raw = sugg.raw || {};
											const isHighlighted = idx === highlightedIndex;

											// Parse attributes JSON
											let attributeMap = {};
											try {
												if (typeof raw.attributes === "string") {
													const parsed = JSON.parse(raw.attributes);
													if (Array.isArray(parsed)) {
														parsed.forEach((attr) => {
															if (attr.name && attr.value) {
																attributeMap[attr.name.toLowerCase()] = attr.value;
															}
														});
													}
												}
											} catch {
												// ignore parse errors
											}

											const getAttr = (names) => {
												for (const name of names) {
													const key = name.toLowerCase();
													if (attributeMap[key]) return attributeMap[key];
												}
												return "-";
											};

											return (
												<tr
													key={sugg.id ?? idx}
													className={isHighlighted ? "active" : ""}
													onMouseDown={(e) => {
														e.preventDefault();
														onSelectSuggestion(sugg);
													}}
													style={{
														cursor: "pointer",
														backgroundColor: isHighlighted ? "#337ab7" : "transparent",
														color: isHighlighted ? "#0000" : "#333",
													}}
												>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "11px",
															fontWeight: "500",
														}}
													>
														{sugg.label}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{raw.category || "-"}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{getAttr(["brand"])}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{getAttr(["part number", "part no"])}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{getAttr(["oem part number", "oem no"])}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{getAttr(["made in", "make"])}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{raw.pack_name || "-"}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "10px",
															color: isHighlighted ? "#fff" : "#666",
														}}
													>
														{getAttr(["weight"])}
													</td>
													<td
														style={{
															padding: "4px 6px",
															fontSize: "11px",
															textAlign: "right",
															fontWeight: "500",
														}}
													>
														{raw.price ?? raw.unit_price ?? raw.cost
															? parseFloat(
																	raw.price ?? raw.unit_price ?? raw.cost
															  ).toFixed(2)
															: "-"}
													</td>
												</tr>
											);
										})}
									</tbody>
								</table>
							</div>
						)}
					</form>
					{/* Cart (replicates register_bak.php layout) */}
					<div style={{ marginTop: "1em" }}>
						<h4>Sale Items</h4>
						{cartArray.length === 0 ? (
							<div className="alert alert-info">No items in cart.</div>
						) : (
							<table className="sales_table_100 table table-striped table-bordered" id="register">
								<thead>
									<tr>
										<th style={{ width: "5%" }}> </th>
										<th style={{ width: "30%" }}>Item</th>
										<th style={{ width: "10%" }}>Price</th>
										<th style={{ width: "10%" }}>Quantity</th>
										<th style={{ width: "15%" }}>Discount</th>
										<th style={{ width: "10%" }}>Total</th>
										<th style={{ width: "5%" }}> </th>
									</tr>
								</thead>
								<tbody>
									{cartArray
										.slice()
										.reverse()
										.map((item, index) => {
											const unitPrice =
												parseFloat(
													item.price ?? item.item_unit_price ?? item.unit_price ?? 0
												) || 0;
											const qty = parseFloat(item.quantity ?? item.quantity_purchased ?? 0) || 0;
											const discountVal = parseFloat(item.discount ?? 0) || 0;
											const lineTotal =
												item.discounted_total != null
													? parseFloat(item.discounted_total)
													: Math.max(0, unitPrice * qty - discountVal);

											return (
												<tr key={item.line || item.item_id || index}>
													<td>
														<button
															type="button"
															className="btn btn-xs btn-danger"
															onClick={() =>
																handleRemoveItem(
																	item.line ??
																		item.item_id ??
																		item.rowid ??
																		item.row ??
																		index
																)
															}
															title="Remove item"
														>
															<span className="glyphicon glyphicon-trash" />
														</button>
													</td>
													<td>{item.name ?? ""}</td>
													<td style={{ textAlign: "right" }}>{unitPrice.toFixed(2)}</td>
													<td style={{ textAlign: "right" }}>{qty}</td>
													<td style={{ textAlign: "right" }}>{discountVal}</td>
													<td style={{ textAlign: "right" }}>{lineTotal.toFixed(2)}</td>
													<td>
														<button
															type="button"
															className="btn btn-xs btn-info"
															onClick={() => handleEditItem(item)}
															title="Edit item"
														>
															<span className="glyphicon glyphicon-pencil" />
														</button>
													</td>
												</tr>
											);
										})}
								</tbody>
							</table>
						)}

						{/* Cart totals summary computed client-side for display */}
						{cartArray.length > 0 &&
							(() => {
								const itemCount = cartArray.length;
								const totalUnits = cartArray.reduce(
									(acc, it) => acc + (parseFloat(it.quantity ?? it.quantity_purchased ?? 0) || 0),
									0
								);
								const subtotal = cartArray.reduce((acc, it) => {
									const unitPrice =
										parseFloat(it.price ?? it.item_unit_price ?? it.unit_price ?? 0) || 0;
									const qty = parseFloat(it.quantity ?? it.quantity_purchased ?? 0) || 0;
									return acc + unitPrice * qty;
								}, 0);
								const total = cartArray.reduce(
									(acc, it) =>
										acc +
										(it.discounted_total != null
											? parseFloat(it.discounted_total)
											: (parseFloat(it.price ?? it.item_unit_price ?? it.unit_price ?? 0) || 0) *
													(parseFloat(it.quantity ?? it.quantity_purchased ?? 0) || 0) -
											  (parseFloat(it.discount ?? 0) || 0)),
									0
								);

								return (
									<div
										id="overall_sale"
										className="panel panel-default"
										style={{ width: "100%", marginTop: "1em" }}
									>
										<div className="panel-body">
											<table className="sales_table_100" id="sale_totals">
												<tbody>
													<tr>
														<th style={{ width: "55%" }}>
															Quantity of items ({itemCount})
														</th>
														<th style={{ width: "45%", textAlign: "right" }}>
															{totalUnits}
														</th>
													</tr>
													<tr>
														<th style={{ width: "55%" }}>Sub Total</th>
														<th style={{ width: "45%", textAlign: "right" }}>
															{subtotal.toFixed(2)}
														</th>
													</tr>
													<tr>
														<th style={{ width: "55%", fontSize: "150%" }}>Total</th>
														<th
															style={{
																width: "45%",
																fontSize: "150%",
																textAlign: "right",
															}}
														>
															{total.toFixed(2)}
														</th>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								);
							})()}
					</div>
					{/* Payment Inputs (inline) - moved just before Complete Sale */}
					<div className="panel panel-default" style={{ marginTop: "1em" }}>
						<div className="panel-body">
							<div>
								<div
									style={{
										display: "inline-flex",
										gap: "0.5rem",
										alignItems: "center",
										flexWrap: "wrap",
									}}
								>
									{payment_options &&
										Object.entries(payment_options).map(([key, label]) => {
											const isCash = key.toLowerCase() === "cash";
											return (
												<div
													key={key}
													style={{
														display: "flex",
														flexDirection: "column",
														alignItems: "stretch",
														gap: "0.25rem",
													}}
												>
													<label
														style={{
															fontSize: "12px",
															fontWeight: isCash ? "bold" : "normal",
															color: isCash ? "#28a745" : "#333",
														}}
													>
														{label}
													</label>
													<input
														type="number"
														min="0"
														step="0.01"
														className="form-control input-sm"
														placeholder={label}
														value={paymentInputs[key] ?? ""}
														onChange={(e) => handlePaymentInputChange(key, e.target.value)}
														style={{
															width: "120px",
															backgroundColor: isCash ? "#f0fff4" : "#fff",
															borderColor: isCash ? "#28a745" : "#ccc",
														}}
														readOnly={isCash}
														title={
															isCash
																? "Cash is auto-calculated based on total and other payments"
																: ""
														}
													/>
												</div>
											);
										})}

									<div
										style={{
											display: "flex",
											alignItems: "flex-end",
											marginLeft: "0.5rem",
											height: "48px",
										}}
									>
										<div style={{ padding: "8px 0" }}>
											<strong style={{ marginRight: "0.5rem" }}>Total:</strong>
											<span style={{ fontSize: "14px", fontWeight: "bold" }}>
												{Number(combinedPaymentTotal || 0).toFixed(2)}
											</span>
										</div>
									</div>

									<div
										style={{
											marginLeft: "auto",
											display: "flex",
											alignItems: "flex-end",
											height: "48px",
										}}
									>
										{/* Payments are now auto-applied on input change */}
									</div>
								</div>
							</div>
						</div>
					</div>
					{/* Finish Sale Button */}
					<div className="panel panel-default">
						<div className="panel-body">
							<button
								className="btn btn-sm btn-success pull-right"
								tabIndex="14"
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
					<div style={{ marginTop: "2em" }}>
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
													<td>{sale.sale_id || ""}</td>
													<td>{sale.sale_time || ""}</td>
													<td>{item.name || ""}</td>
													<td>{sale.vehicle_kilometer || ""}</td>
													<td>{sale.vehicle_avg_oil_km || ""}</td>
													<td>{sale.vehicle_avg_km_day || ""}</td>
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
