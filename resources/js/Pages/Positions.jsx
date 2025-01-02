import React, { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import io from 'socket.io-client';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useForm } from '@inertiajs/react';
import axios from 'axios';

window.io = io;

export default function Positions({ positions }) {
    const [btcPrice, setBtcPrice] = useState('Loading...');
    const [accountBalance, setAccountBalance] = useState('Loading...');
    const [currentLeverage, setCurrentLeverage] = useState({ long: 'Loading...', short: 'Loading...' });
    const [longLeverage, setLongLeverage] = useState('');
    const [shortLeverage, setShortLeverage] = useState('');
    const [leverageUpdateMessage, setLeverageUpdateMessage] = useState('');
    const [posSideExplanation, setPosSideExplanation] = useState('');

    const { data, setData, put, processing } = useForm({
        clOrdID: `order-${Math.random().toString(36).substr(2, 9)}`,
        symbol: 'BTCUSDT',
        side: '',
        posSide: '', // Add posSide field
        orderQtyRq: 0.001,
        trigger_source: 'website_button',
    });

    useEffect(() => {
        const echo = new Echo({
            broadcaster: 'socket.io',
            host: `${import.meta.env.VITE_SOCKETIO_HOST}:${import.meta.env.VITE_SOCKETIO_PORT}`,
        });

        echo.connector.socket.on('connect', () => {
            console.log('Connected to WebSocket server');
        });

        echo.connector.socket.on('btcPrice', (data) => {
            console.log('Received BTC price update:', data.price);
            setBtcPrice(data.price);
        });

        return () => {
            echo.disconnect();
        };
    }, []);

    // Fetch the account balance
    useEffect(() => {
        axios
            .get('/api/account-balance')
            .then((response) => {
                setAccountBalance(response.data.balance);
            })
            .catch((error) => {
                console.error('Error fetching account balance:', error);
                setAccountBalance('Error fetching balance');
            });
    }, []);

    // Derive current leverage from positions
    useEffect(() => {
        const longPosition = positions.find((position) => position.posSide === 'Long');
        const shortPosition = positions.find((position) => position.posSide === 'Short');

        setCurrentLeverage({
            long: longPosition?.leverageRr || 'Not Set',
            short: shortPosition?.leverageRr || 'Not Set',
        });
    }, [positions]);

    // Handle leverage change
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

    // Handle position side selection
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

    // Handle order placement
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

    return (
        <AuthenticatedLayout>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6 sm:px-20 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-bold">Positions</h1>
                            {/* Positions Table */}
                          {positions.length > 0 ? (
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
                                        {positions.map((position, index) => (
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
                                <p className="mt-4 text-red-600">No active positions found. Try refreshing after placing an order.</p>
                            )}
                            <p className="mt-4">Current BTC Price: {btcPrice}</p>
                            <p className="mt-4">Account Balance: {accountBalance} USDT</p>
                            <p className="mt-4">
                                Current Leverage: Long: {currentLeverage.long}, Short: {currentLeverage.short}
                            </p>

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
                                    <div>
                                        <label htmlFor="posSide" className="block text-sm font-medium text-gray-700">
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
                                        <label htmlFor="side" className="block text-sm font-medium text-gray-700">
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
                                        <label htmlFor="orderQtyRq" className="block text-sm font-medium text-gray-700">
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
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}