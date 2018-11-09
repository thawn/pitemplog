module.exports = {
  dist: {
    src: '<%= concat.dist.dest %>',
    dest: 'build/assets/js/jquery_bootstrap_amcharts_custom.min.js'
  },
  conf: {
    src: 'build/assets/js/conf.js',
    dest: 'build/assets/js/conf.min.js'
  }
};