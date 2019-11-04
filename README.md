# iTop-generic-comm-interface
Create a generic network interface, instead of the existing Network an SAN interface

[TOC]

## Changes

This is basically a clone of iTop-generic-comm-interface for two reasons :

1) the name was confusing, as this extension brings much more than a comm interface, here is the complete set

2) due to the grow of the project, some refacturing was needed, but as this will change things also in the database, this need a fresh install on a fresh iTop.

# Goal

Itop come with a network interface really IP oriented. If you add the datacenter module, then you'll also have a SAN interface type.

I plan to create a more generic kind of interface, with a 'connector type' (Fiber, RJ-45, RS-232, ...) and a 'protocol-type' (Ethernet, FC, FCoE, ...)

This should stay on the OSI level 1 or 2, I'm not sure (yet) that this should include high level information (IP address, gateway...)

Of course, I'll have to build also a 'iTop-generic-switch' CI to connect the whole together !

# Warning

This development is in a very early stage. It could be major changes, including in the data structure. You don't want do install this extension in a real iTop environment (not yet !)

**You really really wont do that...**

As this is still a work in progress, there are no 'label' for all text fields.

# Installation

As for all my extensions, just download the zip file, and copy the 'schirrms-...' directory in your extensions directory, then rerun the setup as usual.