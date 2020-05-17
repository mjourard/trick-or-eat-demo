const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common');
const secrets = require('./webpack.secrets');
const path = require('path');

const configs = {
    BACKEND: JSON.stringify('http://localapi.guelphtrickoreat.ca'),
    CHARITABLE_REG_NUM: JSON.stringify('00000 0000 AA0000'),
    CONTACT_ADDR_1: JSON.stringify('77 Demo Address Avenue, Suite 9001'),
    CONTACT_ADDR_2: JSON.stringify('Toronto, Ontario A1A 1A1'),
    CONTACT_PHONE: JSON.stringify('555-555-5555'),
    CONTACT_EMAIL: JSON.stringify('thisisademo@mealexchange.com')
};

const definitions = secrets(configs, './secrets');


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
        new webpack.DefinePlugin(definitions)
    ]
});

