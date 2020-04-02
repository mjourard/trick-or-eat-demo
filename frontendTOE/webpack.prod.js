const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = merge(common('Trick or Eat'), {
    mode: 'production',
    name: 'prod',
    devtool: 'source-map',
    plugins: [
        new webpack.DefinePlugin({
            BACKEND: JSON.stringify('https://api.guelphtrickoreat.ca'),
            ROUTE_HOSTING: JSON.stringify('https://guelphtrickoreat.ca/route-files'),
            CHARITABLE_REG_NUM: JSON.stringify('84052 4581 RR0001'),
            CONTACT_ADDR_1: JSON.stringify('401 Richmond Street West, Suite 365'),
            CONTACT_ADDR_2: JSON.stringify('Toronto, Ontario M5V 3A8'),
            CONTACT_PHONE: JSON.stringify('416-657-4489'),
            CONTACT_EMAIL: JSON.stringify('whereits@mealexchange.com')
        })
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
