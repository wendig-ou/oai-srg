Vagrant.configure("2") do |base|
  base.vagrant.plugins = ["vagrant-vbguest"]

  base.vm.synced_folder '.', '/vagrant', type: 'virtualbox'
  base.vm.network :forwarded_port, host: 3000, guest: 3000, host_ip: '127.0.0.1'
  base.vm.network :forwarded_port, host: 3306, guest: 3306, host_ip: '127.0.0.1'
  base.vm.hostname = 'oai-srg'
  base.vm.provider :virtualbox do |vb|
    vb.memory = 2048
    vb.cpus = 2
    vb.customize ['storagectl', :id, '--name', 'SATA Controller', '--hostiocache', 'off']
  end
  base.vm.provision :shell, path: 'provision.sh', args: 'base'

  base.vm.define 'php5', primary: true do |c|
    c.vm.box = 'debian/jessie64'
    c.vm.provider :virtualbox do |vb|
      vb.name = 'oai-srg-php5'
    end
    c.vm.provision :shell, path: 'provision.sh', args: 'install_php5'
  end

  base.vm.define 'php7' do |c|
    c.vm.box = 'debian/stretch64'
    c.vm.provider :virtualbox do |vb|
      vb.name = 'oai-srg-php7'
    end
    c.vm.provision :shell, path: 'provision.sh', args: 'install_php7'
  end
end
