import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Inertia } from '@inertiajs/inertia';

export default function Trades({ phemexTrades }) {
    const handleSyncHistory = () => {
        if (confirm('Are you sure you want to sync trade history?')) {
            Inertia.get('/trades/sync-history', {
                onSuccess: (page) => {
                    alert(page.props.flash?.success || 'Trade history synced successfully!');
                },
                onError: (error) => {
                    console.error('Sync failed:', error);
                    alert(error.response?.data?.error || 'Failed to sync trade history. Please check the logs.');
                },
            });
        }
    };

    const formatTimestamp = (timestampNs) => {
        const milliseconds = Math.floor(timestampNs / 1e6);
        return new Date(milliseconds).toLocaleString(); // Convert to readable format
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
                            {/* Sync Trade History Button */}
                            <div className="mb-6">
                                <button
                                    onClick={handleSyncHistory}
                                    className="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                >
                                    Sync Trade History
                                </button>
                            </div>

                            {/* Phemex Trades Table */}
                            <div className="mt-8">
                                <h3 className="text-lg font-semibold mb-4">Phemex Trades</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Side</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exec Fee</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {phemexTrades.map((trade) => (
                                                <tr key={trade.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap">{formatTimestamp(trade.transact_time_ns)}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.symbol}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.side}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.exec_qty}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.price}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">{trade.exec_fee}</td>
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
