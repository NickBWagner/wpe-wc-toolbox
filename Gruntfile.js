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
		}
	});

	grunt.loadNpmTasks('grunt-phplint');
	grunt.loadNpmTasks('grunt-phpcs');

	grunt.registerTask('default', [
		'phplint',
		'phpcs'
	]);
};
