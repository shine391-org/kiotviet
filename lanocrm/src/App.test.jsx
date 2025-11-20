import { render } from '@testing-library/react';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';
import App from './App';
import store from './store';

test('renders app without crashing', () => {
  render(
    <Provider store={store}>
      <BrowserRouter>
        <ConfigProvider locale={viVN}>
          <App />
        </ConfigProvider>
      </BrowserRouter>
    </Provider>
  );
});
