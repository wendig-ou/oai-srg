unless Vagrant.has_plugin?("vagrant-vbguest")
  STDERR.puts 'vbguest plugin is not installed, please install it (vargant plugin install vagrant-vbguest)'
  exit 1
end

Vagrant.configure("2") do |config|
  config.vm.define 'default', primary: true do |c|
    c.vm.box = 'debian/stretch64'
    c.vm.synced_folder '.', '/vagrant', type: 'virtualbox'

    c.vm.network :forwarded_port, host: 3000, guest: 3000, host_ip: '127.0.0.1'
    c.vm.network :forwarded_port, host: 3306, guest: 3306, host_ip: '127.0.0.1'
    c.vm.hostname = 'oai-srg'

    c.vm.provider :virtualbox do |vb|
      vb.name = 'oai-srg'
      vb.memory = 1024
      vb.cpus = 2
      vb.customize ['storagectl', :id, '--name', 'SATA Controller', '--hostiocache', 'off']
    end

    c.vm.provision :shell, path: 'provision.sh', args: 'base'
  end
end
