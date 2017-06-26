# Optimized for Vagrant 1.7 and above.
Vagrant.require_version ">= 1.7.0"

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  # Every Vagrant virtual environment requires a box to build off of.Vagrant.configure(2) do |config|

  config.vm.box = "bento/ubuntu-16.04"
  # The hostname for the VM
  config.vm.hostname = "thirty.bees"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  # config.vm.box_check_update = false

  # Disable the new default behavior introduced in Vagrant 1.7, to
  # ensure that all Vagrant machines will use the same SSH key pair.
  # See https://github.com/mitchellh/vagrant/issues/5005
  config.ssh.insert_key = false

  # If true, then any SSH connections made will enable agent forwarding.
  # Default value: false
  config.ssh.forward_agent = false

  # Create an entry in the /etc/hosts file for #{hostname}
  if defined? VagrantPlugins::HostsUpdater
    config.hostsupdater.aliases = ["#{config.vm.hostname}"]
  end

  config.vm.provider "virtualbox" do |v,override|
    # Share an additional folder to the guest VM. The first argument is
    # the path on the host to the actual folder. The second argument is
    # the path on the guest to mount the folder. And the optional third
    # argument is a set of non-required options.
    # config.vm.synced_folder "../data", "/vagrant_data"
    # Provider-specific configuration so you can fine-tune various
    # backing providers for Vagrant. These expose provider-specific options.
    # Example for VirtualBox:
    #
    # config.vm.provider "virtualbox" do |vb|
    #   # Don't boot with headless mode
    #   vb.gui = true
    #
    #   # Use VBoxManage to customize the VM. For example to change memory:
    #   vb.customize ["modifyvm", :id, "--memory", "1024"]
    # end
    v.gui=false

    # Memory
    v.customize ["modifyvm", :id, "--memory", 1024]

    # CPUs
    v.customize ["modifyvm", :id, "--cpus", "1"]

    # Video Ram
    v.customize ["modifyvm", :id, "--vram", "32"]

    # --hwvirtex on|off: This enables or disables the use of hardware virtualization
    # extensions (Intel VT-x or AMD-V) in the processor of your host system;
    v.customize ["modifyvm", :id, "--hwvirtex", "on"]

    # --hpet on|off: This enables/disables a High Precision Event Timer (HPET)
    # which can replace the legacy system timers. This is turned off by default.
    # Note that Windows supports a HPET only from Vista onwards.
    v.customize ["modifyvm", :id, "--hpet", "on"]

    # --pagefusion on|off: Enables/disables (default) the Page Fusion feature.
    # The Page Fusion feature minimises memory duplication between VMs with similar
    # configurations running on the same host. See Section 4.9.2, “Page Fusion” for details.
    v.customize ["modifyvm", :id, "--pagefusion", "on"]

    # --paravirtprovider none|default|legacy|minimal|hyperv|kvm: This setting specifies which
    # paravirtualization interface to provide to the guest operating system.
    v.customize ["modifyvm", :id, "--paravirtprovider", "kvm"]

    # --chipset piix3|ich9: By default VirtualBox emulates an Intel PIIX3 chipset.
    v.customize ["modifyvm", :id, "--chipset", "ich9"]

    v.customize ["setextradata", "global", "GUI/MaxGuestResolution", "any"]
    v.customize ["setextradata", :id, "CustomVideoMode1", "1024x768x32"]
    v.customize ["modifyvm", :id, "--ioapic", "on"]
    v.customize ["modifyvm", :id, "--rtcuseutc", "on"]
    v.customize ["modifyvm", :id, "--clipboard", "bidirectional"]
    v.customize ["modifyvm", :id, "--audio", "none"]
  end

  # Run Ansible from the Vagrant VM
  config.vm.provision "ansible_local" do |ansible|
    ansible.verbose = "vv"
    ansible.playbook = "vagrant/playbooks/vagrant.yml"
  end

  config.vm.network "private_network", ip: "10.0.0.30"
end
