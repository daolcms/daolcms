module.exports = function(grunt) {
	"use strict";

	var banner = '/*! Copyright (C) NAVER <http://www.navercorp.com> */\n';
	var banner_xe_js = banner + '/**!\n * @file   common.js + js_app.js + xml_handler.js + xml_js_filter.js\n * @brief  XE Common JavaScript\n **/\n';

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		phplint: {
			default : {
				options: {
					phpCmd: "php",
				},

				src: [
					"**/*.php",
					"!files/**",
					"!tests/**",
					"!tools/**",
					"!node_modules/**",
					"!libs/**"
				],
			},
		}
	});

	grunt.loadNpmTasks('grunt-phplint');

	grunt.registerTask('default', ['phplint']);
	grunt.registerTask('lint', ['phplint']);
	grunt.registerTask('minify', ['phplint']);
};
