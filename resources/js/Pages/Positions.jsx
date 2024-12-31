import React, { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import io from 'socket.io-client';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

window.io = io;

export default function Positions({ positions }) {
    const [btcPrice, setBtcPrice] = useState('Loading...');

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

    return (
        <AuthenticatedLayout>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6 sm:px-20 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-bold">Positions</h1>
                            <p className="mt-4">Current BTC Price: {btcPrice}</p>
                            {positions.length > 0 ? (
                                <table className="table-auto w-full mt-4">
                                    <thead>
                                        <tr>
                                            <th className="px-4 py-2">Symbol</th>
                                            <th className="px-4 py-2">Position Side</th>
                                            <th className="px-4 py-2">Leverage</th>
                                            <th className="px-4 py-2">Size</th>
                                            <th className="px-4 py-2">Position Margin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {positions.map((position, index) => (
                                            <tr key={index}>
                                                <td className="border px-4 py-2">{position.symbol}</td>
                                                <td className="border px-4 py-2">{position.posSide}</td>
                                                <td className="border px-4 py-2">{position.leverageRr}</td>
                                                <td className="border px-4 py-2">{position.size}</td>
                                                <td className="border px-4 py-2">{position.positionMarginRv}</td>
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