module.exports = {
  src: {
    files: [
      {
        expand: true,
        dot: true,
        cwd: '_src/',
        src: ['**/*'],
        dest: 'build/'}
    ]
  },
  assetsdist: {
    files: [
      {
        expand: true,
        cwd: 'assets/',
        src: ['**/*.*'],
        dest: 'build/assets/'}
    ]
  },
  amchartsimg: {
    files: [
      {
        expand: true,
        cwd: 'node_modules/amcharts3/amcharts/images/',
        src: ['*.*'],
        dest: 'build/assets/img/amcharts/'}
    ]
  },
  conf: {
    files: [
      {
        src: ['assets/js/conf.js'],
        dest: 'build/assets/js/conf.js'}
    ]
  },
  img: {
    files: [
      {
        expand: true,
        cwd: 'assets/img/',
        src: ['*.*'],
        dest: 'build/assets/img/'}
    ]
  },
  bootstrapfonts: {
    files: [
      {
        expand: true,
        cwd: 'node_modules/bootstrap/fonts/',
        src: ['*.*'],
        dest: 'build/assets/fonts/'}
    ]
  },
  bootstrapdist: {
    files: [
      {
        expand: true,
        cwd: 'node_modules/bootstrap/dist/',
        src: ['**/*.*'],
        dest: 'build/assets/'}
    ]
  },
  bootstrapdatepicker: {
    files: [
      {
        src: ['node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.js'],
        dest: 'build/assets/js/bootstrap-datepicker.js'
      },
      {
        src: ['node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css'],
        dest: 'build/assets/css/bootstrap-datepicker3.css'
      }
    ]
  },
  jquerydist: {
    files: [
      {
        expand: true,
        cwd: 'node_modules/jquery/dist/',
        src: ['*.{js,map}'],
        dest: 'build/assets/js'}
    ]
  },
  amchartsdist: {
    files: [
      {
        expand: true,
        cwd: 'node_modules/amcharts3/amcharts/',
        src: ['**/*.*'],
        dest: 'build/assets/amcharts/'}
    ]
  }
}