module.exports = function(grunt) {  
  require('load-grunt-config')(grunt);
  grunt.registerTask(
    'dev',
    [
      'clean',
      'copy:src',
      'copy:assetsdist',
      'copy:amchartsdist',
      'copy:jquerydist',
      'copy:bootstrapdist',
      'copy:bootstrapdatepicker',
      'exec:build'
    ]
  );
  grunt.registerTask(
    'default',
    [
      'clean',
      'copy:src',
      'copy:assetsdist',
      'copy:amchartsimg',
      'copy:bootstrapfonts',
      'copy:conf',
      'concat',
      'uglify',
      'cssmin',
      'processhtml',
      'exec:permissions'
    ]
  );
  grunt.registerTask(
    'build',
    [
      'default',
      'exec:build'
    ]
  );
  grunt.registerTask(
    'deploy',
    [
      'default',
      'exec:deploy'
    ]
  );
  grunt.registerTask(
    'install',
    [
      'default',
      'exec:install',
      'exec:setup'
    ]
  );
  grunt.registerTask(
    'updateimage',
    [
      'default',
      'exec:prepimage',
      'exec:install',
      'exec:updateimage',
      'exec:closeimage'
    ]
  );
  grunt.registerTask('docker', [ 'default', 'exec:docker' ]);
  grunt.registerTask('docker_compose', [ 'exec:docker_compose_down', 'default', 'exec:docker_compose' ]);
  grunt.registerTask('docker_compose_test', [ 'exec:docker_compose_test_down', 'default', 'exec:docker_compose_test' ]);
  grunt.registerTask('docker_compose_test_down', [ 'exec:docker_compose_test_down' ]);
  grunt.registerTask('test', [ 'docker_compose_test', 'exec:run_tests' ]);
  grunt.registerTask('uninstall', ['exec:uninstall']);
};
