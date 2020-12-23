const postcssConfig = require('./postcss.config.js')
const path = require('path')

module.exports = {
        entry: './src/assets/index.ts',
	output: {
		    filename: 'bundle.js',
		    path: path.resolve(__dirname, 'src/assets'),
		  },
	mode: "development",
	module: {
		rules: [
			{
				        test: /\.tsx?$/,
				        use: 'ts-loader',
				        //exclude: [path.resolve(__dirname, 'node_modules'), path.resolve(__dirname, 'vendor')],
				      },
			
				//{
				//	test: /\.css$/,
				//	use: 'postcss-loader',
					//options: postcssConfig
				//}
			
		]
	},
	resolve: {
	    extensions: [ '.tsx', '.ts', '.js' ],
	},
}

