module.exports = function(grunt) {
	"use strict";

	var banner = '/*! Copyright (C) NAVER <http://www.navercorp.com> */\n/*! Copyright (C) DAOL Project. <http://www.daolcms.org> */\n';
	var banner_xe_js = banner + '/**!\n * @file modernizr.js + URI.js + common.js + js_app.js + xml2json.js + xml_handler.js + xml_js_filter.js\n * @brief  XE Common JavaScript\n **/\n';

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		clean: {
			minify: [
				'common/js/xe.js',
				'common/js/xe.min.js',
				'common/css/xe.min.css',
				'common/css/mobile.min.css'
			]
		},
		phplint: {
			default : {
				options: {
					phpCmd: "php",
				},

				src: [
					"**/*.php",
					"!**/*.-legacy.php",
					"!files/**",
					"!tests/**",
					"!tools/**",
					"!node_modules/**",
					"!libs/**"
				],
			},
		},
		concat: {
			'common-js': {
				options: {
					stripBanners: true,
					banner: banner_xe_js
				},
				src: [
					'common/js/modernizr.js',
					'common/js/URI.js',
					'common/js/blankshield.min.js',
					'common/js/common.js',
					'common/js/js_app.js',
					'common/js/xml2json.js',
					'common/js/xml_handler.js',
					'common/js/xml_js_filter.js'
				],
				dest: 'common/js/xe.js'
			},
			'xpresseditor': {
				options: {
					stripBanners: true,
					banner: '/**!\n * @concat Xpress_Editor.js + xe_interface.js \n **/\n'
				},
				src: [
					'modules/editor/skins/xpresseditor/js/Xpress_Editor.js',
					'modules/editor/skins/xpresseditor/js/xe_interface.js',
				],
				dest: 'modules/editor/skins/xpresseditor/js/xpresseditor.js'
			}
		},
		uglify: {
			'common-js': {
				options: {
					banner: banner_xe_js
				},
				files: {
					'common/js/xe.min.js': ['common/js/xe.js'],
				}
			},
			'common-js-plugins': {
				files: {
					'common/js/plugins/jquery.fileupload/js/main.min.js': ['common/js/plugins/jquery.fileupload/js/main.js'],
				}
			},
			'handlebars': {
				files: {
					'common/js/plugins/handlebars/handlebars.min.js': ['common/js/plugins/handlebars/handlebars.js'],
					'common/js/plugins/handlebars.runtime/handlebars.runtime.min.js': ['common/js/plugins/handlebars.runtime/handlebars.runtime.js'],
				}
			},
			'modules': {
				files: {
					'common/js/x.min.js' : ['common/js/x.js'],
					// addon
					'addons/captcha/captcha.min.js' : ['addons/captcha/captcha.js'],
					'addons/resize_image/js/resize_image.min.js' : ['addons/resize_image/js/resize_image.js'],
					// module/editor
					'modules/editor/skins/xpresseditor/js/xpresseditor.min.js': ['modules/editor/skins/xpresseditor/js/xpresseditor.js'],
					'modules/editor/skins/xpresseditor/js/xe_textarea.min.js': ['modules/editor/skins/xpresseditor/js/xe_textarea.js'],
					'modules/editor/skins/ckeditor/js/default.min.js': ['modules/editor/skins/ckeditor/js/default.js'],
					'modules/editor/skins/ckeditor/js/xe_interface.min.js': ['modules/editor/skins/ckeditor/js/xe_interface.js'],
					'modules/editor/skins/ckeditor/js/xe_textarea.min.js': ['modules/editor/skins/ckeditor/js/xe_textarea.js'],
					'modules/editor/tpl/js/editor_common.min.js': ['modules/editor/tpl/js/editor_common.js'],
					'modules/editor/tpl/js/swfupload.min.js': ['modules/editor/tpl/js/swfupload.js'],
					'modules/editor/tpl/js/uploader.min.js': ['modules/editor/tpl/js/uploader.js'],
					'modules/editor/tpl/js/editor.min.js': ['modules/editor/tpl/js/editor.js'],
					'modules/editor/tpl/js/editor_module_config.min.js': ['modules/editor/tpl/js/editor_module_config.js'],
					'modules/editor/tpl/js/editor.app.min.js': ['modules/editor/tpl/js/editor.app.js'],
					// module/admin
					'modules/admin/tpl/js/admin.min.js': ['modules/admin/tpl/js/admin.js'],
					'modules/admin/tpl/js/config.min.js': ['modules/admin/tpl/js/config.js'],
					'modules/admin/tpl/js/menu_setup.min.js': ['modules/admin/tpl/js/menu_setup.js'],
					//module/board
					'modules/board/tpl/js/board.min.js': ['modules/board/tpl/js/board.js'],
					'modules/board/tpl/js/board_admin.min.js': ['modules/board/tpl/js/board_admin.js'],
					'modules/board/skins/default/board.default.min.js': ['modules/board/skins/default/board.default.js'],
					'modules/board/m.skins/default/js/mboard.min.js': ['modules/board/m.skins/default/js/mboard.js'],
					// editor-component-image-gallery
					'modules/editor/components/image_gallery/tpl/gallery.min.js' : ['modules/editor/components/image_gallery/tpl/gallery.js'],
					'modules/editor/components/image_gallery/tpl/list_gallery.min.js' : ['modules/editor/components/image_gallery/tpl/list_gallery.js'],
					'modules/editor/components/image_gallery/tpl/popup.min.js' : ['modules/editor/components/image_gallery/tpl/popup.js'],
					'modules/editor/components/image_gallery/tpl/slide_gallery.min.js' : ['modules/editor/components/image_gallery/tpl/slide_gallery.js'],
					// module/importer
					'modules/importer/tpl/js/importer_admin.min.js': ['modules/importer/tpl/js/importer_admin.js'],
					// modules/widget
					'modules/widget/tpl/js/generate_code.min.js': ['modules/widget/tpl/js/generate_code.js'],
					'modules/widget/tpl/js/widget.min.js': ['modules/widget/tpl/js/widget.js'],
					'modules/widget/tpl/js/widget_admin.min.js': ['modules/widget/tpl/js/widget_admin.js'],
					// modules/poll
					'modules/poll/tpl/js/poll_admin.min.js': ['modules/poll/tpl/js/poll_admin.js'],
					'modules/poll/tpl/js/poll.min.js': ['modules/poll/tpl/js/poll.js'],
					// krzip
					'modules/krzip/tpl/js/admin.min.js': ['modules/krzip/tpl/js/admin.js'],
					'modules/krzip/tpl/js/daumapi.min.js': ['modules/krzip/tpl/js/daumapi.js'],
					'modules/krzip/tpl/js/epostapi.min.js': ['modules/krzip/tpl/js/epostapi.js'],
					'modules/krzip/tpl/js/epostapi.search.min.js': ['modules/krzip/tpl/js/epostapi.search.js'],
				}
			},
			'layout': {
				files: {
					'layouts/daol_official/js/layout.min.js': ['layouts/daol_official/js/layout.js'],
					'layouts/daol_official/js/slides/slides.min.js': ['layouts/daol_official/js/slides/slides.js'],
					'layouts/daol_official/js/slides/slides_start.min.js': ['layouts/daol_official/js/slides/slides_start.js'],
				}
			},
		},
		cssmin: {
			'common': {
				files: {
					'common/css/xe.min.css': ['common/css/xe.css'],
					'common/css/mobile.min.css': ['common/css/mobile.css'],
				}
			},
			'modules': {
				files: {
					'modules/admin/tpl/css/admin.min.css': ['modules/admin/tpl/css/admin.css'],
					'modules/editor/components/image_gallery/tpl/popup.min.css': ['modules/editor/components/image_gallery/tpl/popup.css'],
					'modules/editor/components/image_gallery/tpl/slide_gallery.min.css': ['modules/editor/components/image_gallery/tpl/slide_gallery.css'],
					'modules/widget/tpl/css/widget.min.css': ['modules/widget/tpl/css/widget.css'],
					'modules/poll/tpl/css/poll.min.css': ['modules/poll/tpl/css/poll.css'],
					'modules/poll/skins/default/css/poll.min.css': ['modules/poll/skins/default/css/poll.css'],
					'modules/poll/skins/simple/css/poll.min.css': ['modules/poll/skins/simple/css/poll.css'],
					'modules/editor/skins/xpresseditor/css/default.min.css': ['modules/editor/skins/xpresseditor/css/default.css'],
					'modules/board/skins/default/board.default.min.css': ['modules/board/skins/default/board.default.css'],
					'modules/board/m.skins/default/css/mboard.min.css': ['modules/board/m.skins/default/css/mboard.css'],
					// krzip
					'modules/krzip/tpl/css/default.min.css': ['modules/krzip/tpl/css/default.css'],
					'modules/krzip/tpl/css/popup.min.css': ['modules/krzip/tpl/css/popup.css'],
				}
			},
			'layout': {
				files: {
					'layouts/daol_official/css/layout.min.css': ['layouts/daol_official/css/layout.css'],
				}
			},
		},
		jshint: {
			files: [
				'Gruntfile.js',
				'common/js/*.js',
				'modules/admin/tpl/js/*.js',
				'modules/board/tpl/js/*.js',
				'modules/board/skins/*/*.js',
				'modules/editor/tpl/js/*.js',
				'modules/menu/tpl/js/*.js',
				'modules/widget/tpl/js/*.js',
			],
			options : {
            	reporterOutput: '',
				ignores : [
					'**/jquery*.js',
					'**/swfupload.js',
					'**/**.min.js',
					'**/*-packed.js',
					'**/*.compressed.js',
					'**/jquery-*.js',
					'**/jquery.*.js',
					'common/js/URI.js',
					'common/js/blankshield.min.js',
					'common/js/html5.js',
					'common/js/modernizr.js',
					'common/js/x.js',
					'common/js/xe.js',
					'common/js/xml2json.js',
					'vendor/**',
					'tests/**',
				]
			}
		},
		csslint: {
			'common-css': {
				options: {
					import : 2,
					'adjoining-classes' : false,
					'box-model' : false,
					'duplicate-background-images' : false,
					'ids' : false,
					'important' : false,
					'overqualified-elements' : false,
					'qualified-headings' : false,
					'star-property-hack' : false,
					'underscore-property-hack' : false,
				},
				src: [
					'common/css/*.css',
					'!common/css/bootstrap.css',
					'!common/css/bootstrap-responsive.css',
					'!**/*.min.css',
					'!vendor/**',
					'!tests/**',
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-phplint');

	grunt.registerTask('default', ['jshint', 'csslint']);
	grunt.registerTask('lint', ['jshint', 'csslint', 'phplint']);
	grunt.registerTask('minify', ['jshint', 'csslint', 'clean', 'concat', 'uglify', 'cssmin']);
};
