import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function Positions({ positions: initialPositions }) {
    const [positions, setPositions] = useState(initialPositions);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [error, setError] = useState(null);

    const refreshPositions = () => {
        setIsRefreshing(true);
        router.get('/positions', {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                setPositions(page.props.positions);
                setError(null);
            },
            onError: (errors) => {
                setError('Failed to refresh positions.');
                console.error('Error refreshing positions:', errors);
            },
            onFinish: () => setIsRefreshing(false),
        });
    };

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['positions'],
                onSuccess: (page) => {
                    setPositions(page.props.positions);
                },
                onError: (errors) => {
                    console.error('Error during auto-refresh:', errors);
                },
            });
        }, 60000); // Refresh every 60 seconds

        return () => clearInterval(interval); // Cleanup interval on unmount
    }, []);

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Positions</h2>}
        >
            <Head title="Positions" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium">Current Positions</h3>

                            {/* Refresh Button */}
                            <button
                                onClick={refreshPositions}
                                className={`px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 my-4 ${
                                    isRefreshing ? 'opacity-50 cursor-not-allowed' : ''
                                }`}
                                disabled={isRefreshing}
                            >
                                {isRefreshing ? 'Refreshing...' : 'Refresh Positions'}
                            </button>

                            {error && <p className="text-red-600">{error}</p>}

                            {/* Positions Table */}
                            {positions.length > 0 ? (
                                <table className="table-auto w-full mt-4">
                                    <thead>
                                        <tr>
                                            <th className="border px-4 py-2">Symbol</th>
                                            <th className="border px-4 py-2">Side</th>
                                            <th className="border px-4 py-2">Leverage</th>
                                            <th className="border px-4 py-2">Size</th>
                                            <th className="border px-4 py-2">Position Margin</th>
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
