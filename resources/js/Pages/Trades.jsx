import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function Trades({ phemexTrades: initialTrades }) {
    const [phemexTrades, setPhemexTrades] = useState(initialTrades);

    useEffect(() => {
        const syncTrades = async () => {
            try {
                // Sync trade history
                await axios.get('/api/phemex-trades/sync');
                console.log('Trade history synced successfully!');

                // Fetch user-specific trades
                const updatedTradesResponse = await axios.get('/api/phemex-trades');
                setPhemexTrades(updatedTradesResponse.data);
            } catch (error) {
                console.error('Failed to sync trade history:', error);
            }
        };

        syncTrades();
    }, []);

    const formatTimestamp = (timestampNs) => {
        const milliseconds = Math.floor(timestampNs / 1e6);
        return new Date(milliseconds).toLocaleString();
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Trades</h2>}
        >
            <Head title="Trades" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="mt-8">
                                <h3 className="text-lg font-semibold mb-4">Phemex Trades</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Side</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position Side</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exec Fee</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exec Value</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed PnL</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {phemexTrades.map((trade) => (
                                                <tr key={trade.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap">{formatTimestamp(trade.transact_time_ns)}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.symbol}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.side}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.pos_side}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.exec_qty}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.price}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.exec_fee}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.exec_value}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.closed_pnl}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
