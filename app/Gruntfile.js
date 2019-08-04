module.exports = function (grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		concat: {
			options: {
				separator: ';'
			},
			dist: {
				src: ['src/**/*.js'],
				dest: 'dist/<%= pkg.name %>.js'
			}
		},
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
			},
			dist: {
				files: {
					'dist/<%= pkg.name %>.min.js': ['<%= concat.dist.dest %>']
				}
			}
		},
		ngconstant: {
			options: {
				name: 'config',
				dest: 'config.js',
				constants: {
					package: grunt.file.readJSON('package.json')
				},
				values: {
					debug: true
				}
			},
			dev: {
				options: {
					dest: './path/to/new/constants/file.js'
				},
				constants: {
					ENV: {
						name: 'dev'
					}
				}
			},
			prod: {
				options: {
					dest: './path/to/new/constants/file.js'
				},
				constants: {
					ENV: {
						name: 'prod'
					}
				}
			},
			build: {}
		},
		watch: {
			files: ['Gruntfile.js', 'src/**/*.js', 'test/**/*.js'],
			tasks: [ 'qunit']
		}
	});

	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-ng-constant');

	grunt.registerTask('dev', [
		'ngconstant:dev']);
	grunt.registerTask('test', ['ngconstant:dev',  'concat']);
	grunt.registerTask('prod', ['ngconstant:prod',  'concat', 'uglify']);

	grunt.registerTask('default', [  'concat', 'uglify']);

};