const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');
const secrets = require('./webpack.secrets');
const TerserPlugin = require('terser-webpack-plugin');

const configs = {
    BACKEND: JSON.stringify('https://api.guelphtrickoreat.ca'),
    CHARITABLE_REG_NUM: JSON.stringify('84052 4581 RR0001'),
    CONTACT_ADDR_1: JSON.stringify('401 Richmond Street West, Suite 365'),
    CONTACT_ADDR_2: JSON.stringify('Toronto, Ontario M5V 3A8'),
    CONTACT_PHONE: JSON.stringify('416-657-4489'),
    CONTACT_EMAIL: JSON.stringify('whereits@mealexchange.com')
};

const definitions = secrets(configs, './secrets');

module.exports = merge(common('Trick or Eat'), {
    mode: 'production',
    name: 'prod',
    devtool: 'source-map',
    plugins: [
        new webpack.DefinePlugin(definitions)
    ],
    optimization: {
        minimizer: [
            new TerserPlugin({
                sourceMap: true, // Must be set to true if using source-maps in production
                terserOptions: {
                    compress: {
                        drop_console: true,
                    },
                },
            }),
        ],
    },
});
