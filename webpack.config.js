const postcssConfig = require('./postcss.config.js')

module.exports = {
	module: {
		rules: [
			use: [
				{
					loader: 'postcss-loader',
					options: postcssConfig
				}
			]
		]
	}
}

