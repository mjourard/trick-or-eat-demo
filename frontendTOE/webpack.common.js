const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const secrets = require('./webpack.secrets');

const config = secrets({}, './secrets');

module.exports = title => {
    return {
        entry: {
            app: './app/app.module.js',
        },
        plugins: [
            new CleanWebpackPlugin(),
            new HtmlWebpackPlugin(Object.assign({
                title: title,
                template: './app/index.html',
                inject: 'true'
            }, config)),
            new CopyPlugin({
		patterns: [
                    {
                        from: './*/**/*.html',
                        to: '[path]/[name].[ext]',
                        context: './app/'
                    },
                    {from: 'css/**', to: '[path]/[name].[ext]', context: './app/'},
                    {from: 'img/**', to: '[path]/[name].[ext]', context: './app/'}
		]
            })

        ],
        output: {
            filename: '[name].bundle.js',
            path: path.resolve(__dirname, 'dist')
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        'style-loader', // creates style nodes from JS strings
                        'css-loader', // translates CSS into CommonJS
                        'sass-loader', // compiles Sass to CSS, using Node Sass by default
                    ]
                },
                {
                    test: /\.css$/,
                    use: [
                        'style-loader',
                        'css-loader'
                    ]
                },
                {
                    test: /\.(png|svg|jpg|gif|ico)$/,
                    use: [
                        'file-loader'
                    ]
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    use: [
                        'file-loader'
                    ]
                },
                {
                    test: /\.(csv|tsv)$/,
                    use: [
                        'csv-loader'
                    ]
                },
                {
                    test: /\.xml$/,
                    use: [
                        'xml-loader'
                    ]
                },
                {
                    test: /\.json$/,
                    use: [
                        'json-loader'
                    ]
                }
            ]
        }
    }
};
