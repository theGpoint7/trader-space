import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="bg-gray-50 text-black dark:bg-black dark:text-white min-h-screen flex items-center justify-center">
                <div className="text-center">
                    <h1 className="text-2xl font-bold mb-4">Welcome to this Training-Space.xyz App</h1>
                    <p className="text-lg mb-6">
                        This app is brought to you by Laravel, Breeze, WSL, Composer, PHP, Sail, Node, and NPM.
                    </p>
                    <div className="flex justify-center space-x-4">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-md px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 transition"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-4 py-2 bg-green-500 text-white hover:bg-green-600 transition"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md px-4 py-2 bg-gray-500 text-white hover:bg-gray-600 transition"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
