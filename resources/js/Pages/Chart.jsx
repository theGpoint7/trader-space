import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import TradingViewWidgetChart from '@/Components/TradingViewWidgetChart';
import TradingViewWidgetHeatmap from '@/Components/TradingViewWidgetHeatmap';

export default function Chart() {
  return (
    <AuthenticatedLayout>
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div className="p-6 sm:px-20 bg-white border-b border-gray-200">
              <h1 className="text-2xl font-bold">TradingView Chart</h1>
              <TradingViewWidgetChart />
              <h1 className="text-2xl font-bold mt-8">Crypto Coins Heatmap</h1>
              <TradingViewWidgetHeatmap />
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}