import React, { useEffect, useState } from 'react';
import io from 'socket.io-client'; // Simplified WebSocket connection
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';

export default function Positions({ positions }) {
    const { message, error } = usePage().props; // Fetch message and error from Inertia props
    const [btcPrice, setBtcPrice] = useState('Loading...');
    const [accountBalance, setAccountBalance] = useState('Loading...');
    const [currentLeverage, setCurrentLeverage] = useState({ long: 'Loading...', short: 'Loading...' });
    const [longLeverage, setLongLeverage] = useState('');
    const [shortLeverage, setShortLeverage] = useState('');
    const [leverageUpdateMessage, setLeverageUpdateMessage] = useState('');
    const [posSideExplanation, setPosSideExplanation] = useState('');
    const [loading, setLoading] = useState(true); // Loading state

    const { data, setData, put, processing } = useForm({
        clOrdID: `order-${Math.random().toString(36).substr(2, 9)}`,
        symbol: 'BTCUSDT',
        side: '',
        posSide: '', // Add posSide field
        orderQtyRq: 0.001,
        trigger_source: 'website_button',
    });

    // Fallback for positions
    const validPositions = Array.isArray(positions) ? positions : [];

    // WebSocket listener for BTC price
    useEffect(() => {
        const socket = io(process.env.NODE_ENV === 'production' 
            ? 'https://smart-turkey-crisp.ngrok-free.app' // Ngrok domain for production
            : 'https://localhost:4000'); // Localhost for development
    
        socket.on('connect', () => {
            console.log('Connected to WebSocket server');
        });
    
        socket.on('btcPrice', (data) => {
            console.log('Received BTC price update:', data);
            setBtcPrice(data.price || 'Error: Invalid data'); // Update state with the price
        });
    
        socket.on('connect_error', (err) => {
            console.error('WebSocket connection error:', err);
        });
    
        return () => {
            socket.disconnect(); // Cleanup the WebSocket connection on component unmount
        };
    }, []);
    

    // Fetch account balance and update loading state
    useEffect(() => {
        axios
            .get('/api/account-balance')
            .then((response) => {
                setAccountBalance(response.data.balance);
            })
            .catch((error) => {
                console.error('Error fetching account balance:', error);
                setAccountBalance('Error fetching balance');
            })
            .finally(() => {
                setLoading(false);
            });
    }, []);

    // Derive current leverage from validPositions
    useEffect(() => {
        const longPosition = validPositions.find((position) => position.posSide === 'Long');
        const shortPosition = validPositions.find((position) => position.posSide === 'Short');

        setCurrentLeverage({
            long: longPosition?.leverageRr || 'Not Set',
            short: shortPosition?.leverageRr || 'Not Set',
        });
    }, [validPositions]);

    const handleLeverageChange = () => {
        if (longLeverage < 1 || longLeverage > 200 || shortLeverage < 1 || shortLeverage > 200) {
            setLeverageUpdateMessage('Leverage must be between 1 and 200 for both long and short positions.');
            return;
        }

        axios
            .put('/api/change-leverage', null, {
                params: {
                    symbol: 'BTCUSDT',
                    longLeverage: longLeverage,
                    shortLeverage: shortLeverage,
                },
            })
            .then(() => {
                setLeverageUpdateMessage('Leverage updated successfully!');
                setCurrentLeverage({ long: longLeverage, short: shortLeverage });
            })
            .catch((error) => {
                console.error('Error updating leverage:', error);
                setLeverageUpdateMessage('Error updating leverage. Please try again.');
            });
    };

    const handlePosSideChange = (value) => {
        setData('posSide', value);
        if (value === 'Long') {
            setPosSideExplanation('When you want to place a long position, select Buy. To sell your long position, select Sell.');
        } else if (value === 'Short') {
            setPosSideExplanation('When you want to place a short position, select Sell. To close your short position, select Buy.');
        } else {
            setPosSideExplanation('');
        }
    };

    const handlePlaceOrder = (e) => {
        e.preventDefault();

        put('/trades/place-order', {
            onSuccess: () => alert('Order placed successfully!'),
            onError: (errors) => {
                console.error(errors);
                alert('Error placing order.');
            },
        });
    };

    // Display loading spinner while loading
    if (loading) {
        return <p>Loading...</p>;
    }

    return (
        <AuthenticatedLayout>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6 sm:px-20 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-bold">Positions</h1>
                            {loading ? (
                                <p>Loading...</p>
                            ) : (
                                <>
                                    {message && <p className="text-green-600">{message}</p>}
                                    {error && <p className="text-red-600">{error}</p>}
                                    <p className="mt-4">Current BTC Price: {btcPrice}</p>
                                    <p className="mt-4">Account Balance: {accountBalance} USDT</p>
                                    {/* Positions Table */}
                                    {validPositions.length > 0 ? (
                                        <table className="table-auto w-full mt-8">
                                            <thead>
                                                <tr>
                                                    <th className="px-4 py-2">Symbol</th>
                                                    <th className="px-4 py-2">Position Side</th>
                                                    <th className="px-4 py-2">Leverage</th>
                                                    <th className="px-4 py-2">Size</th>
                                                    <th className="px-4 py-2">Position Margin</th>
                                                    <th className="px-4 py-2">Entry Price</th>
                                                    <th className="px-4 py-2">Liquidation Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {validPositions.map((position, index) => (
                                                    <tr key={index}>
                                                        <td className="border px-4 py-2">{position.symbol}</td>
                                                        <td className="border px-4 py-2">{position.posSide || '—'}</td>
                                                        <td className="border px-4 py-2">{position.leverageRr || '—'}</td>
                                                        <td className="border px-4 py-2">{position.size || '—'}</td>
                                                        <td className="border px-4 py-2">{position.positionMarginRv || '—'}</td>
                                                        <td className="border px-4 py-2">{position.avgEntryPriceRp || '—'}</td>
                                                        <td className="border px-4 py-2">{position.liquidationPriceRp || '—'}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <p className="mt-4 text-red-600">
                                            No active positions found. Try refreshing after placing an order.
                                        </p>
                                    )}
                                    {/* Leverage Update Section */}
                                    <div className="mt-6">
                                        <h2 className="text-xl font-bold">Update Leverage</h2>
                                        <div className="flex flex-col mt-2 space-y-2">
                                            <input
                                                type="number"
                                                min="1"
                                                max="200"
                                                value={longLeverage}
                                                onChange={(e) => setLongLeverage(e.target.value)}
                                                className="border px-4 py-2"
                                                placeholder="Set Long Leverage"
                                            />
                                            <input
                                                type="number"
                                                min="1"
                                                max="200"
                                                value={shortLeverage}
                                                onChange={(e) => setShortLeverage(e.target.value)}
                                                className="border px-4 py-2"
                                                placeholder="Set Short Leverage"
                                            />
                                            <button
                                                onClick={handleLeverageChange}
                                                className="bg-blue-500 text-white px-4 py-2 rounded"
                                            >
                                                Update Leverage
                                            </button>
                                        </div>
                                        {leverageUpdateMessage && (
                                            <p className="mt-2 text-sm text-red-600">{leverageUpdateMessage}</p>
                                        )}
                                    </div>
                                    {/* Place Order Section */}
                                    <div className="mt-8">
                                        <h2 className="text-xl font-bold">Place Order</h2>
                                        <form onSubmit={handlePlaceOrder} className="mt-4 space-y-4">
                                            <p className="mt-4">
                                                Current Leverage: Long: {currentLeverage.long}, Short: {currentLeverage.short}
                                            </p>
                                            <div>
                                                <label
                                                    htmlFor="posSide"
                                                    className="block text-sm font-medium text-gray-700"
                                                >
                                                    Position Side
                                                </label>
                                                <select
                                                    id="posSide"
                                                    value={data.posSide}
                                                    onChange={(e) => handlePosSideChange(e.target.value)}
                                                    required
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                >
                                                    <option value="" disabled>
                                                        Select Position Side
                                                    </option>
                                                    <option value="Long">Long</option>
                                                    <option value="Short">Short</option>
                                                </select>
                                                {posSideExplanation && (
                                                    <p className="mt-2 text-sm text-gray-600">{posSideExplanation}</p>
                                                )}
                                            </div>
                                            <div>
                                                <label
                                                    htmlFor="side"
                                                    className="block text-sm font-medium text-gray-700"
                                                >
                                                    Side
                                                </label>
                                                <select
                                                    id="side"
                                                    value={data.side}
                                                    onChange={(e) => setData('side', e.target.value)}
                                                    required
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                >
                                                    <option value="" disabled>
                                                        Select Side
                                                    </option>
                                                    <option value="buy">Buy</option>
                                                    <option value="sell">Sell</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    htmlFor="orderQtyRq"
                                                    className="block text-sm font-medium text-gray-700"
                                                >
                                                    Order Quantity
                                                </label>
                                                <input
                                                    type="number"
                                                    id="orderQtyRq"
                                                    value={data.orderQtyRq}
                                                    onChange={(e) => setData('orderQtyRq', parseFloat(e.target.value))}
                                                    step="0.001"
                                                    min="0.001"
                                                    required
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                />
                                            </div>
                                            <button
                                                type="submit"
                                                className="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                            >
                                                Place Order
                                            </button>
                                        </form>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
    
}