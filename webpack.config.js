const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");
const webpack = require("webpack");

module.exports = {
  ...defaultConfig,
  entry: {
    'test-block': path.resolve(process.cwd(), `blocks/test-block/index.js`),
    login: [
      path.resolve(process.cwd(), "src", "login.jsx"),
      path.resolve(process.cwd(), "src", "connect.jsx"),
      path.resolve(process.cwd(), "src", "css", "login.scss"),
    ],
  },
  resolve: {
    ...defaultConfig.resolve,
    extensions: ["jsx", ...defaultConfig.resolve.extensions],
  },
  plugins: [
    ...defaultConfig.plugins,
    new webpack.ProvidePlugin({
      Buffer: ["buffer", "Buffer"],
      process: "process/browser",
    }),
  ],
};
