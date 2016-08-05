var phpPaths = ['wpe-wc-toolbox.php', 'inc/**/*.php'];

module.exports = function(grunt) {

	grunt.initConfig({
		phplint: {
			plugin: phpPaths
		},
		phpcs: {
			plugin: {
				src: phpPaths
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'WordPress-Core'
			}
		},
		compress: {
			plugin: {
				options: {
					archive: 'build/wpe-wc-toolbox.zip'
				},
				files: [
					{src: ['wpe-wc-toolbox.php'], dest: '/'},
					{src: ['inc/**/*'], dest: '/'}
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-phplint');
	grunt.loadNpmTasks('grunt-phpcs');
	grunt.loadNpmTasks('grunt-contrib-compress');

	grunt.registerTask('default', [
		'phplint',
		'phpcs'
	]);

	grunt.registerTask('build', [
		'compress'
	]);
};
