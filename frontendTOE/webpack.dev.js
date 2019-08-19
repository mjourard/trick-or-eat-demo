const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');
const path = require('path');

module.exports = merge(common('Dev Trick or Eat'), {
    mode: 'development',
    name: 'dev',
    devtool: 'inline-source-map',
    devServer: {
        // contentBase: path.join(__dirname, 'dist'),
        hot: true,
        // inline: false, //pretty cool option to load in via iframe and get hot module replacement
        compress: true,
        watchOptions: {
            poll: true
        },
        allowedHosts: [
            '.guelphtrickoreat.ca'
        ]
    },
    plugins: [
        new webpack.DefinePlugin({
            BACKEND: JSON.stringify('http://localapi.guelphtrickoreat.ca:8000'),
            ROUTE_HOSTING: JSON.stringify('http://local.guelphtrickoreat.ca:8000/route-files')
        })
    ]
});

