const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');
const secrets = require('./webpack.secrets');
const TerserPlugin = require('terser-webpack-plugin');

const configs = {
    BACKEND: JSON.stringify('https://api.guelphtrickoreat.ca'),
    CHARITABLE_REG_NUM: JSON.stringify('00000 0000 AA0000'),
    CONTACT_ADDR_1: JSON.stringify('77 Demo Address Avenue, Suite 9001'),
    CONTACT_ADDR_2: JSON.stringify('Toronto, Ontario A1A 1A1'),
    CONTACT_PHONE: JSON.stringify('555-555-5555'),
    CONTACT_EMAIL: JSON.stringify('thisisademo@mealexchange.com')
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
