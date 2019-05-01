module.exports = function (grunt) {
  var username = grunt.option('user') || 'pi';
  var hostname = grunt.option('host') || 'diez-templog-1';
  var basedir = grunt.option('basedir') || '';
  var imagefile = grunt.option('image') || '';
  var installdir = basedir+'/usr/local/share/templog';

  return {
    build: {
      cmd: 'jekyll build'
    },
    serve: {
      cmd: 'jekyll serve --watch'
    },
    deploy: {
      cmd: [
        'chmod a+x build/_bin/install.sh',
        'ssh ' + username + '@' + hostname + ' "sudo mkdir -p ' + installdir +' && sudo chown pi:pi ' + installdir +'"',
        'rsync --progress -a --delete -e "ssh -q" build/ ' + username + '@' + hostname + ':' + installdir + '/',
        'ssh ' + username + '@' + hostname + ' "' + installdir + '/_bin/install.sh"'
      ].join('&&')
    },
    install: {
      cmd: [
        'chmod a+x build/_bin/install.sh',
        'sudo mkdir -p "' + installdir +'"',
        'sudo chown -R 1000:1000 "' + installdir + '"',
        'rsync --progress -a --delete build/ "' + installdir + '/"',
        'sudo chown -R 1000:1000 "' + installdir + '"'
      ].join('&&')
    },
    setup: {
      cmd: '"' + installdir + '/_bin/install.sh"'
    },
    prepimage: {
      cmd: [
        'losetup -P /dev/loop1 ' + imagefile,
        'mount /dev/loop1p2 ' + basedir
      ].join('&&')
    },
    updateimage: {
      cmd: [
        'cp build/_sbin/setup_templog_once ' + basedir + '/etc/init.d/',
        'chmod a+x ' + basedir + '/etc/init.d/setup_templog_once',
        'cd ' + basedir + '/etc/rc3.d',
        'ln -fs ../init.d/setup_templog_once S01setup_templog_once',
        'cd ~'
      ].join('&&')
    },
    closeimage: {
      cmd: [
        'umount ' + basedir,
        'losetup -d /dev/loop1'
      ].join('&&')
    },
    docker: {
      cmd: 'docker build -t pitemplog .'
    },
    uninstall: {
      cmd: [
        'chmod a+x build/_bin/uninstall.sh',
        'build/_bin/uninstall.sh'
      ].join('&&')
    }
  }
}