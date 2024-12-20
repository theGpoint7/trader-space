import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Inertia } from '@inertiajs/inertia';

export default function Trades({ phemexTrades }) {
    const generateRandomOrderID = () => `order-${Math.random().toString(36).substr(2, 9)}`;

    const { data, setData, put, processing } = useForm({
        clOrdID: generateRandomOrderID(),
        symbol: 'BTCUSDT',
        side: '',
        orderQtyRq: 0.001,
        trigger_source: 'website_button',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        put('/trades/place-order', {
            onSuccess: () => alert('Order placed successfully!'),
            onError: (errors) => {
                console.error(errors);
                alert(`Error: ${JSON.stringify(errors)}`);
            },
        });
    };

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

                            <form onSubmit={handleSubmit}>
                                {/* Order ID Input */}
                                <div className="mb-4">
                                    <label htmlFor="clOrdID" className="block text-sm font-medium text-gray-700">
                                        Order ID
                                    </label>
                                    <input
                                        type="text"
                                        id="clOrdID"
                                        value={data.clOrdID}
                                        onChange={(e) => setData('clOrdID', e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>

                                {/* Symbol Input */}
                                <div className="mb-4">
                                    <label htmlFor="symbol" className="block text-sm font-medium text-gray-700">
                                        Symbol
                                    </label>
                                    <input
                                        type="text"
                                        id="symbol"
                                        value={data.symbol}
                                        onChange={(e) => setData('symbol', e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>

                                {/* Side Input */}
                                <div className="mb-4">
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
                                        <option value="" disabled>Select Side</option>
                                        <option value="buy">Buy</option>
                                        <option value="sell">Sell</option>
                                    </select>
                                </div>

                                {/* Order Quantity Input */}
                                <div className="mb-4">
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

                                {/* Trigger Source Input */}
                                <div className="mb-4">
                                    <label htmlFor="trigger_source" className="block text-sm font-medium text-gray-700">
                                        Trigger Source
                                    </label>
                                    <input
                                        type="text"
                                        id="trigger_source"
                                        value={data.trigger_source}
                                        onChange={(e) => setData('trigger_source', e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>

                                {/* Submit Button */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    Place Order
                                </button>
                            </form>

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
