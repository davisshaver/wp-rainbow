const webpack = require('webpack');
const path = require('path');

module.exports = (env) => ({
    devtool: 'eval-source-map',
    entry: './public/js/index.jsx',
    externals: {
        'react': 'React',
        'react-dom': 'ReactDOM',
    },
    mode: env.production ? 'production' : 'development',
    module: {
        rules: [
            {
                test: () => true,
                sideEffects: true,
            },
            {
                test: /\.(css)$/,
                use: ['style-loader', 'css-loader'],
            },
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                use: ['babel-loader'],
            }
        ],
    },
    optimization: {
        usedExports: false,
    },
    output: {
        path: path.resolve(__dirname, 'build', 'js'),
        filename: 'index.js',
    },
    resolve: {
        extensions: ['*', '.js', '.jsx'],
    },
    plugins: [
        new webpack.ProvidePlugin({
            Buffer: ['buffer', 'Buffer'],
            process: 'process/browser',
        }),
    ],
});
