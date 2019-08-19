const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');

module.exports = merge(common('Trick or Eat'), {
    mode: 'production',
    name: 'prod',
    devtool: 'source-map',
    plugins: [
        new webpack.DefinePlugin({
            BACKEND: JSON.stringify('https://api.guelphtrickoreat.ca'),
            ROUTE_HOSTING: JSON.stringify('https://guelphtrickoreat.ca/route-files')
        })
    ]
});