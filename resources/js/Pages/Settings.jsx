import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Settings() {
    const { flash, brokerApiKeys } = usePage().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        broker: 'Phemex',
        apiKey: '',
        apiSecret: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/settings/save-broker', {
            onSuccess: () => reset(['apiKey', 'apiSecret']),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Settings
                </h2>
            }
        >
            <Head title="Settings" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {flash?.success && (
                                <p className="mb-4 text-green-600">{flash.success}</p>
                            )}

                            <h3 className="mb-4 text-lg font-medium text-gray-700">
                                Existing API Keys
                            </h3>

                            {brokerApiKeys && brokerApiKeys.length > 0 ? (
                                <ul>
                                    {brokerApiKeys.map((key, index) => (
                                        <li key={index} className="mb-4">
                                            <p>
                                                <strong>Broker:</strong> {key.broker_name}
                                            </p>
                                            <p>
                                                <strong>API Key:</strong> {key.api_key}
                                            </p>
                                            <p>
                                                <strong>API Secret:</strong> {key.api_secret}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p>No API keys found.</p>
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
                                        <option value="Phemex">Phemex</option>
                                        <option value="Mexc">Mexc</option>
                                        <option value="Kucoin">Kucoin</option>
                                    </select>
                                </div>

                                <div className="mb-4">
                                    <label
                                        htmlFor="apiKey"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        API ID
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

                                <div className="mb-4">
                                    <label
                                        htmlFor="apiSecret"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        API Secret
                                    </label>
                                    <input
                                        type="text"
                                        id="apiSecret"
                                        value={data.apiSecret}
                                        onChange={(e) => setData('apiSecret', e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.apiSecret && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.apiSecret}
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
