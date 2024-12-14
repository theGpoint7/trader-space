import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { data, setData, post, processing, errors, reset } = useForm({
        broker: 'Mexc',
        apiKey: '',
    });

    const { flash } = usePage().props || {}; // Add default fallback to avoid errors

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/dashboard/save-broker', {
            onSuccess: () => reset('apiKey'),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Safely access flash.success */}
                            {flash?.success && (
                                <p className="mb-4 text-green-600">{flash.success}</p>
                            )}

                            <h3 className="mb-4 text-lg font-medium text-gray-700">
                                Add Broker API Info
                            </h3>

                            <form onSubmit={handleSubmit}>
                                <div className="mb-4">
                                    <label
                                        htmlFor="broker"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Select Broker
                                    </label>
                                    <select
                                        id="broker"
                                        value={data.broker}
                                        onChange={(e) => setData('broker', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="Mexc">Mexc</option>
                                    </select>
                                </div>

                                <div className="mb-4">
                                    <label
                                        htmlFor="apiKey"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        API Key
                                    </label>
                                    <input
                                        type="text"
                                        id="apiKey"
                                        value={data.apiKey}
                                        onChange={(e) => setData('apiKey', e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.apiKey && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.apiKey}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Save
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
