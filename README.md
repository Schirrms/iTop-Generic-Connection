# iTop-Generic-Connection
Creates Generic Connection Devices and Interfaces, instead of specialized Network Devices /interfaces, SAN Devices/interfaces...

This extension is an evolution of the now discontinued iTop-generic-comm-interface

# Goal

Itop come with a network interface really IP oriented. If you add the datacenter module, then you'll also have a SAN interface type.

This extension adds a more generic kind of interface, with a 'connector type' (Fiber, RJ-45, RS-232, ...) and a 'protocol-type' (Ethernet, FC, FCoE, ...)

Virtual interfaces are also available, and link between virtual interfaces and others interfaces (using the same protocol) is possible, allowing a complete (and complex :)) representation of the configuration.

Also, this extension permit direct attach between devices so don't be surprised to see the possibility to interconnect servers (for a back to back connection, maybe)

Redundancy is available also : you can have a virtual interface over two physical interfaces and says that only life can continue with only one physical interfaces up.

There is also a 'Generic Connection Device' for the same reason.

Also, you build the connection between the equipment at an interface level (Eth0 of server1 is connected on port 4 of switch0) and iTop builds for you the higher dependencies (server1 depends on switch0)

This stays on the OSI level 1 or 2, I'm not sure (yet) that this should include high level information (IP address, gateway...)

# Usage

After installation, you have one new kind of CI, Generic Connection Device. This device can be a Network Switch, a SAN Switch, or any kind of device you use to interconnect other CI. One could choose to put physical firewall in that category.

You'll also find for all your connectable CI two nex tabs :

* Physical Connection Interface(s) are just that : interfaces you can put a cable on to interconnect to other devices
* Virtual Connection Interface(s) are internal connector, linking together the CI.

Let's see an example : One hypervisor with two 1 Gb/s interfaces for the management, two 10 Gb/s interfaces for vMotion an Virtual Machines, two unused 1 Gb/s interfaces and two SAS connection to a direct attached SAS storage.

Here the view of the physical interfaces :

![ESX01 physical Interfaces](images/esx01-physical-interfaces.png)

(Don't worry about the 'cable id' column, that is a 'work in progress' in another extension)

And the view of the virtual interfaces :

![ESX01 virtual Interfaces](images/esx01-virtual-interfaces.png)

OK, so far, so good, but what for ?

For once, all kind of interfaces are in one tab and I think it's easier to manage

Also, you can build in iTop nearly any 'real life configuration', and you can show it. For instance here is the 'depend on' view for the Vmotion VMkernel 

# Installation

As for all my extensions, just download the zip file, and copy the 'schirrms-...' directory in your extensions directory, then rerun the setup as usual.

Or you can instead just download a 'release zip' and unzip this release in your extension directory.

# Releases

* [0.7.3-beta]( ./schirrms-generic-connection-release-0.7.3-beta.zip)	2019-11-17	First 'public beta', stable enough in my opinion