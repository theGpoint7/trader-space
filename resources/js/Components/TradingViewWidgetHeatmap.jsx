import React, { useEffect, useRef, memo } from 'react';

function TradingViewWidgetChart() {
  const container = useRef();

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://s3.tradingview.com/tv.js';
    script.async = true;
    script.onload = () => {
      if (container.current) {
        new window.TradingView.widget({
          "container_id": "tradingview-widget-container__widget",
          "autosize": true,
          "symbol": "BINANCE:BTCUSD",
          "interval": "D",
          "timezone": "Etc/UTC",
          "theme": "dark",
          "style": "1",
          "locale": "en",
          "toolbar_bg": "#f1f3f6",
          "enable_publishing": false,
          "allow_symbol_change": true,
          "watchlist": [
            "BITSTAMP:BTCUSD",
            "COINBASE:BTCUSD",
            "BINANCE:BTCUSDT.P",
            "CRYPTOCAP:BTC.D",
            "INDEX:BTCUSD",
            "BINANCE:ETHUSDT",
            "BINANCE:SOLUSDT",
            "BINANCE:XRPUSDT",
            "CRYPTO:BTCUSD",
            "COINBASE:ETHUSD",
            "BYBIT:BTCUSDT.P",
            "BINANCE:BTCUSD"
          ],
          "details": true,
          "hotlist": true,
          "calendar": false,
          "show_popup_button": true,
          "popup_width": "1000",
          "popup_height": "650",
          "support_host": "https://www.tradingview.com"
        });
      }
    };
    container.current.appendChild(script);
  }, []);

  return (
    <div className="tradingview-widget-container h-[800px]" ref={container}>
      <div id="tradingview-widget-container__widget" className="tradingview-widget-container__widget"></div>
      <div className="tradingview-widget-copyright">
        <a href="https://www.tradingview.com/" rel="noopener nofollow" target="_blank">
          <span className="blue-text">Track all markets on TradingView</span>
        </a>
      </div>
    </div>
  );
}

export default memo(TradingViewWidgetChart);