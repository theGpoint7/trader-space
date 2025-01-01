import React, { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import io from 'socket.io-client';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import axios from 'axios';

window.io = io;

export default function Positions({ positions }) {
    const [btcPrice, setBtcPrice] = useState('Loading...');
    const [accountBalance, setAccountBalance] = useState('Loading...');
    const [currentLeverage, setCurrentLeverage] = useState({ long: 'Loading...', short: 'Loading...' });
    const [longLeverage, setLongLeverage] = useState('');
    const [shortLeverage, setShortLeverage] = useState('');
    const [leverageUpdateMessage, setLeverageUpdateMessage] = useState('');

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

    // Fetch the account balance on component mount
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
            .then((response) => {
                setLeverageUpdateMessage('Leverage updated successfully!');
                setCurrentLeverage({ long: longLeverage, short: shortLeverage });
            })
            .catch((error) => {
                console.error('Error updating leverage:', error);
                setLeverageUpdateMessage('Error updating leverage. Please try again.');
            });
    };

    return (
        <AuthenticatedLayout>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6 sm:px-20 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-bold">Positions</h1>
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

                            {positions.length > 0 ? (
                                <table className="table-auto w-full mt-4">
                                    <thead>
                                        <tr>
                                            <th className="px-4 py-2">Symbol</th>
                                            <th className="px-4 py-2">Position Side</th>
                                            <th className="px-4 py-2">Leverage</th>
                                            <th className="px-4 py-2">Size</th>
                                            <th className="px-4 py-2">Position Margin</th>
                                            <th className="px-4 py-2">Entry Price</th>
                                            <th className="px-4 py-2">Liquidation Price</th>
                                            <th className="px-4 py-2">Unrealized PnL</th>
                                            <th className="px-4 py-2">Realized PnL</th>
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
                                                <td className="border px-4 py-2">{position.unRealisedPnlRv || '—'}</td>
                                                <td className="border px-4 py-2">{position.cumClosedPnlRv || '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="mt-4 text-red-600">No active positions found. Try refreshing after placing an order.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
