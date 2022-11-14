# Introduction

Welcome to the kubernetes workshop! 🚀  
This workshop is intended to give you a basic understanding of Docker, Helm & Kubernetes.  
In this workshop we will set up a simple application within minikube to test everything locally.  

Let's get it!

# Glossary

## Kubernetes
also known as k8s, is an open-source system for automating deployment, scaling, and management of containerized applications.

## Docker
used to build containerize applications which can be shipped to a k8s cluster

## Helm
Helm Charts help you define, install, and upgrade even the most complex Kubernetes application. Charts are easy to create, version, share, and publish — so start using Helm and stop the copy-and-paste.

## NGINX
NGINX is a simple webserver that will serve our PHP application

## Horizontal Pod Autoscaling
Horizontal pod autoscaling (HPA in short) is used to scale pods horizontally.  
This means it will scale the number of pods up or down based on a metric you configure. 
Most likely this metric is based on CPU or Memory usage. Custom metrics are possible as well but out of scope for this workshop.  
_Be aware that we will **not** be using node autoscaling because we run minikube, which runs on your machine, which you cant scale :)_

# Workshop topics

In this workshop we will cover the following topics:

- Building, linking and running docker containers locally
- Setting up and tearing down a simple hello-world application with a plain kubernetes manifest
- Ingresses, Services, Deployments & CronJobs with Helm
- Troubleshooting / investigating resource events
- Executing commands inside a running container
- Investigating pods
- Exercises to gain hands-on experience

# Workshop goal

> Get a basic understanding of docker, kubernetes, helm and investigating a kubernetes cluster to identify issues

At the end of this workshop you will have the following setup:

- A multi-stage Dockerfile to create the NGINX and PHP image
- A NGINX service that serves content from your PHP application
- An autoscaling PHP service that can write a message to the database
- A MySQL service that uses a persistent volume to store data

You will find helm chart files en Dockerfile configurations in this workshop project which will guide you through the workshop. Some are already complete, some need some extra work as exercise.

# Prerequisites

Below you will find a list of tools that you will need to complete this workshop.  
Start by installing these tools before you continue.

System specs:
- 2 CPUs or more
- 2GB of free memory
- 20GB of free disk space
- Internet connection
- Container or virtual machine manager, such as: Docker, Hyperkit, Hyper-V, KVM, Parallels, Podman, VirtualBox, or VMware Fusion/Workstation

Install / setup:

## Install Git

Linux: `sudo apt install git` 
Mac: You can use Homebrew https://brew.sh/ and then execute `brew install git`

Others: https://github.com/git-guides/install-git

## Install Docker

For detailed instructions go here: https://docs.docker.com/get-docker/  
Linux: https://docs.docker.com/engine/install/debian/#install-using-the-repository

**Post installation steps for Linux**  
To run docker as non-root user **don't skip this step**:  
_For details: https://docs.docker.com/engine/install/linux-postinstall/_

```bash
sudo groupadd docker
sudo usermod -aG docker $USER
newgrp docker
```

You now have set up docker to run without root, to verify you did it correctly:

```bash
docker run hello-world
# Hello from Docker!
# This message shows that your installation appears to be working correctly.
# ....
```

## Install Minikube

**stop after installation, rest is in this workshop**  
Installation guide: https://minikube.sigs.k8s.io/docs/start/

Mac: If you use Homebrew, you can use `brew install minikube`

Execute the following to check you're ready to go:  
_output could differ based on your local setup / OS_

```bash
docker -vvv
# Docker version 20.10.18, build b40c2f6

minikube start
# 😄  minikube v1.27.0 on Debian bookworm/sid
# ❗  Kubernetes 1.25.0 has a known issue with resolv.conf. minikube is using a workaround that should work for most use cases.
# ❗  For more information, see: https://github.com/kubernetes/kubernetes/issues/112135
# ✨  Automatically selected the docker driver. Other choices: ssh, none
# 📌  Using Docker driver with root privileges
# 👍  Starting control plane node minikube in cluster minikube
# 🚜  Pulling base image ...
# 💾  Downloading Kubernetes v1.25.0 preload ...
#     > preloaded-images-k8s-v18-v1...:  385.37 MiB / 385.37 MiB  100.00% 27.93 M
#     > gcr.io/k8s-minikube/kicbase:  386.75 MiB / 386.76 MiB  100.00% 22.40 MiB 
#     > gcr.io/k8s-minikube/kicbase:  0 B [________________________] ?% ? p/s 15s
# 🔥  Creating docker container (CPUs=2, Memory=7900MB) ...
# 🐳  Preparing Kubernetes v1.25.0 on Docker 20.10.17 ...
#     ▪ Generating certificates and keys ...
#     ▪ Booting up control plane ...
#     ▪ Configuring RBAC rules ...
# 🔎  Verifying Kubernetes components...
#     ▪ Using image gcr.io/k8s-minikube/storage-provisioner:v5
# 🌟  Enabled addons: storage-provisioner, default-storageclass
# 🏄  Done! kubectl is now configured to use "minikube" cluster and "default" namespace by default
```

Lastly we need to enable the nginx ingress add-on, this way an ingress created in the cluster we can make it reachable via our host network.  
This means that the k8s add-on will automatically create a bridge for us when it detects an ingress resource.  
Public cloud providers will create a load balancer with a public IP for example.  
To make it even more juicy, you can even install another _controller_ that also watches the ingresses and automatically creates DNS records for you.  

Back to our setup, enable the add-on for our minikube cluster as following:

```bash
minikube addons enable ingress                                                                                                                                                                   Sun 02 Oct 2022 12:17:42 PM CEST

# 💡  ingress is an addon maintained by Kubernetes. For any concerns contact minikube on GitHub.
# You can view the list of minikube maintainers at: https://github.com/kubernetes/minikube/blob/master/OWNERS
#     ▪ Using image k8s.gcr.io/ingress-nginx/kube-webhook-certgen:v1.1.1
#     ▪ Using image k8s.gcr.io/ingress-nginx/controller:v1.2.1
#     ▪ Using image k8s.gcr.io/ingress-nginx/kube-webhook-certgen:v1.1.1
# 🔎  Verifying ingress addon...
# 🌟  The 'ingress' addon is enabled
```

If all commands succeeded you should now be able to open the dashboard:
```bash
minikube dashboard
# 🔌  Enabling dashboard ...
#     ▪ Using image docker.io/kubernetesui/dashboard:v2.6.0
#     ▪ Using image docker.io/kubernetesui/metrics-scraper:v1.0.8
# 🤔  Verifying dashboard health ...
# 🚀  Launching proxy ...
# 🤔  Verifying proxy health ...
# 🎉  Opening http://127.0.0.1:46285/api/v1/namespaces/kubernetes-dashboard/services/http:kubernetes-dashboard:/proxy/ in your default browser...
# Opening in existing browser session.
```

In the minikube dashboard you can browse the resources within your local cluster.  
Leave minikube for now, we will continue with this later.

> Later on we will mainly use `kubectl` but feel free to use this interface if you prefer / helps you to understand what is going on.

# Preparing our environment

We must learn to walk before we can run so, lets start simple.   

We start by creating the environment locally with pure Docker. When we have that down, we will move on to deploying to kubernetes with Helm.  
In the kubernetes part we will use the docker images we build from the first stage of this workshop. The second and final stage will be existing of kubernetes and challenges.  

Good luck! 🦾

## Clone the workshop repo

Clone this repository into your machine because the commands in this workshop will reference files from this repo.

```bash
cd ~/Documents # <-- feel free to change this to whatever you like 😉
git clone git@github.com:MadeRelevant/workshop-k8s.git workshop
cd workshop
ls -la
# drwxrwxr-x  7 <user> <group>  4096 Sep 30 17:30 .
# drwxrwxr-x 10 <user> <group>  4096 Sep 30 12:05 ..
# drwxrwxr-x  5 <user> <group>  4096 Sep 30 16:33 docker
# -rw-rw-r--  1 <user> <group>   830 Sep 30 16:53 Dockerfile
# drwxrwxr-x  9 <user> <group>  4096 Sep 30 17:29 .git
# -rw-rw-r--  1 <user> <group>     6 Sep 30 17:18 .gitignore
# drwxrwxr-x  3 <user> <group>  4096 Sep 29 14:59 helm
# drwxrwxr-x  2 <user> <group>  4096 Sep 30 17:30 .idea
# -rw-rw-r--  1 <user> <group> 20043 Sep 30 17:30 README.md
# drwxrwxr-x  2 <user> <group>  4096 Sep 30 17:15 src
```

> From here on all commands are executed from the `~/Documents/workshop` directory, or the one you've chosen.

## Getting our containers ready

In order to deploy your containers to a cluster you need to create an image from them. An image is a snapshot of the last build which can be deployed to your cluster.
In the build steps defined in your Dockerfile you will decide what will be part of your image, such as code files and NGINX configuration for example.  

For this workshop we will create 3 containers: database, nginx (webserver) and a PHP container.  
The first step is to build them, run them and test that we can get our application working locally. When that works we proceed to deploying our application using Helm into our local minikube cluster.  

The reason we will connect and run them locally first is for you to get a better grasp of what tools such as docker-compose and kubernetes is actually doing for you under the hood (automatically).  

### Database container

We need a database for our workshop project. It is kept as simple as possible and won't spend too much time on this because it is not what this workshop is about.  

Start by building the database container:  

```bash
# Build the database container
  # --target because we have a multistage Dockerfile we have to define what part we want to build
  # -t is the image tag, which we reference when we want to actually run this container later on
  # . (dot) is the build context which is the relative path within the Dockerfile
DOCKER_BUILDKIT=1 docker build --target=db -t workshop-db .
# [+] Building 10.3s (8/8) FINISHED                                                                                            
#  => [internal] load build definition from Dockerfile                                                                    0.0s
#  => => transferring dockerfile: 301B                                                                                    0.0s
#  => [internal] load .dockerignore                                                                                       0.0s
#  => => transferring context: 2B                                                                                         0.0s
#  => [internal] load metadata for docker.io/library/mysql:8                                                              1.4s
#  => [auth] library/mysql:pull token for registry-1.docker.io                                                            0.0s
#  => [db 1/2] FROM docker.io/library/mysql:8@sha256:b9532b1edea72b6cee12d9f5a78547bd3812ea5db842566e17f8b33291ed2921     8.7s
#  => => resolve docker.io/library/mysql:8@sha256:b9532b1edea72b6cee12d9f5a78547bd3812ea5db842566e17f8b33291ed2921        0.0s
# ...
#  => [internal] load build context                                                                                       0.0s
#  => => transferring context: 262B                                                                                       0.0s
#  => [db 2/2] COPY docker/mysql/init.sql /docker-entrypoint-initdb.d/init.sql                                            0.2s
#  => exporting to image                                                                                                  0.0s
#  => => exporting layers                                                                                                 0.0s
#  => => writing image sha256:fadfa232fc5c53e6018131b70e7c362042d8ab9c1c36ee989c655a60dad1879a                            0.0s
#  => => naming to docker.io/library/db                                                                                   0.0s
```

Start the container as following:
```bash
# Start the database container we just built
  # --rm so the container is removed after we shut it down
  # workshop-db is the docker image we want to run. We defined this at build time with the -t (tag) flag
docker run --name db --rm workshop-db
TODO: ADD what this does
# 2022-09-30 14:43:16+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.30-1.el8 started.
# 2022-09-30 14:43:16+00:00 [Note] [Entrypoint]: Switching to dedicated user 'mysql'
# 2022-09-30 14:43:16+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.30-1.el8 started.
# 2022-09-30 14:43:16+00:00 [Note] [Entrypoint]: Initializing database files
# ...
# 2022-09-30T14:43:24.040643Z 0 [System] [MY-010931] [Server] /usr/sbin/mysqld: ready for connections. Version: '8.0.30'  socket: '/var/run/mysqld/mysqld.sock'  port: 3306  MySQL Community Server - GPL.
```

To verify all is correct you should see `ready for connections` at the end of the last command. If this works proceed to the next step.

> You will have to keep the terminal open and open a new one for the following steps.

FIXME: cannot close DB

### NGINX container

```bash
DOCKER_BUILDKIT=1 docker build --target=nginx -t workshop-nginx .
# [+] Building 3.9s (8/8) FINISHED                                                                                                                                                                                                                              
#  => [internal] load build definition from Dockerfile                                                                                                                                                                                                     0.0s
#  => => transferring dockerfile: 188B                                                                                                                                                                                                                     0.0s
#  => [internal] load .dockerignore                                                                                                                                                                                                                        0.0s
#  => => transferring context: 2B                                                                                                                                                                                                                          0.0s
#  => [internal] load metadata for docker.io/library/nginx:latest                                                                                                                                                                                          1.5s
#  => [auth] library/nginx:pull token for registry-1.docker.io                                                                                                                                                                                             0.0s
#  => [internal] load build context                                                                                                                                                                                                                        0.1s
#  => => transferring context: 548B                                                                                                                                                                                                                        0.0s
#  => [nginx 1/2] FROM docker.io/library/nginx:latest@sha256:0b970013351304af46f322da1263516b188318682b2ab1091862497591189ff1                                                                                                                              2.3s
#  => => resolve docker.io/library/nginx:latest@sha256:0b970013351304af46f322da1263516b188318682b2ab1091862497591189ff1                                                                                                                                    0.0s
# ....                                                                                                                                                              0.0s
#  => [nginx 2/2] COPY docker/nginx/vhost.conf /etc/nginx/conf.d/vhost.conf                                                                                                                                                                                0.1s
#  => exporting to image                                                                                                                                                                                                                                   0.0s
#  => => exporting layers                                                                                                                                                                                                                                  0.0s
#  => => writing image sha256:3c859a3821bc1309c12e15f5955431d6deb4214c1c53f589d227df760bd53142
```

For testing and demonstration purposes we will (try to) start the containers locally first, without kubernetes.  

```bash
# Start the nginx container we just built
  # -p exposes port 80 of the container. Locally we can connect to port 8080 which connects to port 80 within the container
docker run -p 8080:80 --rm workshop-nginx
# 2022/09/29 11:04:34 [emerg] 1#1: host not found in upstream "php" in /etc/nginx/conf.d/vhost.conf:10
# nginx: [emerg] host not found in upstream "php" in /etc/nginx/conf.d/vhost.conf:10
```

> If a port is already in use, close the dockers of your other projects.

 As you can see it is trying to look up the host `php` which doesn't exist yet. 
 Let's fix this by building and starting the php container.  

### PHP container

```bash
DOCKER_BUILDKIT=1 docker build --target=php -t workshop-php .
# [+] Building 3.7s (8/8) FINISHED                                                                                                                             
#  => [internal] load build definition from Dockerfile                                                                                                    0.0s
#  => => transferring dockerfile: 199B                                                                                                                    0.0s
#  => [internal] load .dockerignore                                                                                                                       0.0s
#  => => transferring context: 2B                                                                                                                         0.0s
#  => [internal] load metadata for docker.io/library/php:8-fpm-alpine                                                                                     1.6s
#  => [auth] library/php:pull token for registry-1.docker.io                                                                                              0.0s
#  => [internal] load build context                                                                                                                       0.0s
#  => => transferring context: 25B                                                                                                                        0.0s
#  => [php 1/2] FROM docker.io/library/php:8-fpm-alpine@sha256:edeae85ac28e00082f2304bab361139830432eef4b6614c1a371cad7288f0576                           2.0s
#  => => resolve docker.io/library/php:8-fpm-alpine@sha256:edeae85ac28e00082f2304bab361139830432eef4b6614c1a371cad7288f0576                               0.0s
# ...
#  => [php 2/2] COPY src /code                                                                                                                            0.1s
#  => exporting to image                                                                                                                                  0.0s
#  => => exporting layers                                                                                                                                 0.0s
#  => => writing image sha256:2f02325e696ab3f621e145e8deddf70c678812774d8627734357270ca4300b5b                                                            0.0s
#  => => naming to docker.io/library/workshop-php                                                                                                         0.0s

# Start the PHP container by running:
  # --name so we can reference this container from the NGINX container
  # --rm so the container is removed after we shut it down
  # --link db so we can connect to our database inside our PHP script
  # workshop-php is the image tag to use, this is created by the -t flag in the build step before
docker run --name php --link db --rm workshop-php
# [30-Sep-2022 14:06:28] NOTICE: fpm is running, pid 1
# [30-Sep-2022 14:06:28] NOTICE: ready to handle connections
```

Now we have the PHP container running, lets try and run the NGINX container again, but have it _network linked_ with the PHP container:
```bash
docker run --link php --rm workshop-nginx
# /docker-entrypoint.sh: /docker-entrypoint.d/ is not empty, will attempt to perform configuration
# /docker-entrypoint.sh: Looking for shell scripts in /docker-entrypoint.d/
# /docker-entrypoint.sh: Launching /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
# 10-listen-on-ipv6-by-default.sh: info: Getting the checksum of /etc/nginx/conf.d/default.conf
# 10-listen-on-ipv6-by-default.sh: info: Enabled listen on IPv6 in /etc/nginx/conf.d/default.conf
# /docker-entrypoint.sh: Launching /docker-entrypoint.d/20-envsubst-on-templates.sh
# /docker-entrypoint.sh: Launching /docker-entrypoint.d/30-tune-worker-processes.sh
# /docker-entrypoint.sh: Configuration complete; ready for start up
# 2022/09/30 14:18:04 [notice] 1#1: using the "epoll" event method
# 2022/09/30 14:18:04 [notice] 1#1: nginx/1.23.1
# 2022/09/30 14:18:04 [notice] 1#1: built by gcc 10.2.1 20210110 (Debian 10.2.1-6) 
# 2022/09/30 14:18:04 [notice] 1#1: OS: Linux 5.19.0-76051900-generic
# 2022/09/30 14:18:04 [notice] 1#1: getrlimit(RLIMIT_NOFILE): 1048576:1048576
# 2022/09/30 14:18:04 [notice] 1#1: start worker processes
# 2022/09/30 14:18:04 [notice] 1#1: start worker process 31
# ...
# 2022/09/30 14:18:04 [notice] 1#1: start worker process 46
```

We now have our Database, NGINX and PHP containers running 🥳.  
Let's verify this. First we check if the expected containers are running:  

```bash
docker ps
# CONTAINER ID   IMAGE            COMMAND                  CREATED         STATUS         PORTS      NAMES
# 8c24578858af   workshop-php     "docker-php-entrypoi…"   21 minutes ago   Up 21 minutes   9000/tcp                                php
# d94bc04fd241   workshop-db      "docker-entrypoint.s…"   32 minutes ago   Up 32 minutes   3306/tcp, 33060/tcp                     db
# d808d7c6232c   workshop-nginx   "/docker-entrypoint.…"   44 minutes ago   Up 44 minutes   0.0.0.0:8080->80/tcp, :::8080->80/tcp   nifty_wescoff
```

Secondly, we should be able to see our application in the browser by visiting: http://localhost:8080  
If you can interact with the web application you can proceed to the next step: Deploying to kubernetes 🙌

FIXME: No does not work

# Deploying to the minikube cluster

Now we have tested that our containers are able to build and, we have a running web application we can start deploying.    
Because we didn't push our images to a public registry we need to connect with the docker daemon used by the minikube cluster.    
This makes it possible for k8s to use images that we've built locally. This is a nice trick to know and will make testing/development much faster.    

TODO: what does eval $(minikube docker-env) do
```bash
# linux/osx users: 
eval $(minikube docker-env) # leave the dollar sign out for fish users
# windows:
  # todo... todo... todo-todo-todo...

# verify you're connected to the minikube docker daemon
docker ps
# CONTAINER ID   IMAGE                  COMMAND                  CREATED          STATUS          PORTS     NAMES
# dda20e90c325   k8s.gcr.io/pause:3.6   "/pause"                 22 seconds ago   Up 20 seconds             k8s_POD_php-6cb68c979c-s69zh_default_70c1d913-5894-41ef-96c9-11dbb45eab94_0
# 2b9194ca0722   6e38f40d628d           "/storage-provisioner"   5 minutes ago    Up 5 minutes              k8s_storage-provisioner_storage-provisioner_kube-system_e5cbf9a2-5cfd-43ef-8b98-685a628eb56d_3
# 8deddcb5e974   115053965e86           "/metrics-sidecar"       6 minutes ago    Up 6 minutes              k8s_dashboard-metrics-scraper_dashboard-metrics-scraper-b74747df5-fjpfh_kubernetes-dashboard_63c72c84-5e02-48e3-b1a3-a48e4abfa21b_1
# bc4524816cb9   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_dashboard-metrics-scraper-b74747df5-fjpfh_kubernetes-dashboard_63c72c84-5e02-48e3-b1a3-a48e4abfa21b_1
# 4b2af324cff0   1042d9e0d8fc           "/dashboard --insecu…"   6 minutes ago    Up 6 minutes              k8s_kubernetes-dashboard_kubernetes-dashboard-54596f475f-sccnd_kubernetes-dashboard_b3a8d66e-90d0-453d-b27d-d101b56fe577_1
# bc34020d11cd   5185b96f0bec           "/coredns -conf /etc…"   6 minutes ago    Up 6 minutes              k8s_coredns_coredns-565d847f94-6f7wn_kube-system_51089726-f3bf-4f71-ab05-a9c70a60f168_1
# b41e78ad7a73   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_kubernetes-dashboard-54596f475f-sccnd_kubernetes-dashboard_b3a8d66e-90d0-453d-b27d-d101b56fe577_1
# 94ea0a29d55b   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_coredns-565d847f94-6f7wn_kube-system_51089726-f3bf-4f71-ab05-a9c70a60f168_1
# 6502958503ec   58a9a0c6d96f           "/usr/local/bin/kube…"   6 minutes ago    Up 6 minutes              k8s_kube-proxy_kube-proxy-g99fl_kube-system_5d50f506-6c19-4cef-ba14-5d06720cf9bc_1
# 531017b014f4   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_kube-proxy-g99fl_kube-system_5d50f506-6c19-4cef-ba14-5d06720cf9bc_1
# 0caffb7c1900   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_storage-provisioner_kube-system_e5cbf9a2-5cfd-43ef-8b98-685a628eb56d_1
# eb84619b91a4   4d2edfd10d3e           "kube-apiserver --ad…"   6 minutes ago    Up 6 minutes              k8s_kube-apiserver_kube-apiserver-minikube_kube-system_16de73f898f4460d96d28cf19ba8407f_1
# 8a55b4d276e6   1a54c86c03a6           "kube-controller-man…"   6 minutes ago    Up 6 minutes              k8s_kube-controller-manager_kube-controller-manager-minikube_kube-system_77bee6e3b99b4016b81d7d949c58a789_1
# 264cab4ecefa   bef2cf311509           "kube-scheduler --au…"   6 minutes ago    Up 6 minutes              k8s_kube-scheduler_kube-scheduler-minikube_kube-system_b564e2b429a630c74cbb01d3c4b7a228_1
# d2f53f5b5400   a8a176a5d5d6           "etcd --advertise-cl…"   6 minutes ago    Up 6 minutes              k8s_etcd_etcd-minikube_kube-system_bd495b7643dfc9d3194bd002e968bc3d_1
# 3e41dee7e08d   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_etcd-minikube_kube-system_bd495b7643dfc9d3194bd002e968bc3d_1
# 818ba5223aef   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_kube-scheduler-minikube_kube-system_b564e2b429a630c74cbb01d3c4b7a228_1
# 54108810062a   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_kube-controller-manager-minikube_kube-system_77bee6e3b99b4016b81d7d949c58a789_1
# 1989a777fb8a   k8s.gcr.io/pause:3.6   "/pause"                 6 minutes ago    Up 6 minutes              k8s_POD_kube-apiserver-minikube_kube-system_16de73f898f4460d96d28cf19ba8407f_1
```

Within the **same shell** we can now build and tag our images _again_:
> **Important:** If you switch to another shell you need to connect to the minikube daemon first again using the `eval` command

```bash
DOCKER_BUILDKIT=1 docker build --target=db -t workshop-db .
DOCKER_BUILDKIT=1 docker build --target=nginx -t workshop-nginx .
DOCKER_BUILDKIT=1 docker build --target=php -t workshop-php .
```

## Deploying with kubectl only

Let's try and run a php container as simple as possible:
```bash
kubectl run php --image=workshop-php --image-pull-policy=Never                                                                                                                                Sun 02 Oct 2022 11:29:07 AM CEST
# pod/php created

kubectl get pods
# NAME   READY   STATUS    RESTARTS   AGE
# php    1/1     Running   0          3s

kubectl logs php                                                                                                                                                                                  Sun 02 Oct 2022 11:29:15 AM CEST
# [02-Oct-2022 09:29:13] NOTICE: fpm is running, pid 1
# [02-Oct-2022 09:29:13] NOTICE: ready to handle connections
```

Hurray, ready to handle connections!  🎉  

Running a specific container over the CLI like this might be useful for very simple scenarios.  
Such simple scenario could be a mysql-client container to connect to the database for example. When you exit this container it will be removed from the cluster again.

In production, you often have way more parameters for a deployment. Think of: env var injection, secrets, volumes, autoscaling etc.  
To define all these settings you can use a kubernetes manifest. For example:  https://raw.githubusercontent.com/kubernetes/website/main/content/en/examples/controllers/nginx-deployment.yaml
Typically you would create several manifests to connect deployments to services. Then you add a cronjob that re-uses the image to run commands periodically and so on.  

Writing plain yaml files sounds great but will be hard to maintain very quick. This is where Helm comes into play.

Let's clean up after ourselves before we continue:

```bash
kubectl delete pod php
# pod "php" deleted
```

## Installing helm

See: https://helm.sh/docs/intro/install/

Mac: `brew install helm`

## Deploying with Helm

> **Important:** make sure you are still using the shell where you are connected to the minikube daemon!  

Take some time to go over the contents inside the helm directory first.  
We have a values.yaml which are fed into the helm/templates/*.yaml files.  

When you have a basic understanding of what is defined in the helm directory you can start installing the chart in your minikube cluster:  

ERROR: Error: Kubernetes cluster unreachable: Get "https://127.0.0.1:64412/version": net/http: TLS handshake timeout

```bash
#  --install so we can re-use this command even when it was already installed
#  workshop is a name how we can identify this chart is installed
#  ./helm is the directory from where Helm should read the Helm files
helm upgrade --install workshop ./helm                                                                                                                                                   454ms  Sun 02 Oct 2022 12:13:10 PM CEST
# Release "workshop" does not exist. Installing it now.
# NAME: workshop
# LAST DEPLOYED: Sun Oct  2 12:13:15 2022
# NAMESPACE: default
# STATUS: deployed
# REVISION: 1
# TEST SUITE: None
```

What this chart will install:
- A database service which our PHP container will use to connect
- A database persistent volume claim so, we can store our data which is not lost after killing the minikube cluster
- A database deployment pointing to our earlier build image
- A NGINX ingress to make our application reachable via the URL we defined in values.yaml
- A NGINX service to which the Ingress will connect
- A NGINX deployment which will serve our web application
- A PHP service which NGINX will connect to
- A PHP deployment which serves PHP-FPM

You can also verify / look-up which helm charts are installed as following:
```bash
#  -ls for listing
#  -A for all namespaces
helm -ls -A
# NAME    	NAMESPACE	REVISION	UPDATED                                 	STATUS  	CHART         	APP VERSION
# workshop	default  	1       	2022-10-02 12:13:15.175585404 +0200 CEST	deployed	workshop-0.0.1
```

To test your setup you need to point `workshop.test` to `$ minikube ip`.  

Linux/OSX users could do:
```bash
minikube ip
# 192.168.49.2
echo "192.168.49.2 workshop.test" >> /etc/hosts 
```

After you did this, and you point your browser to the url `http://workshop.test` you should now see the same web application as we did with our local setup.  

## Inspect the cluster

A very important set of commands I will run over to help you investigate what is actually running within the cluster.  
These commands also will help you investigate where potential issues occur(ed).  
Kubernetes is an event driven system, you can query _all_ events like this:

```bash
# all events in the default namespace
kubectl get events -n default --sort-by='.metadata.creationTimestamp'
# LAST SEEN   TYPE      REASON                         OBJECT                              MESSAGE
# 84m         Normal    NodeHasNoDiskPressure          node/minikube                       Node minikube status is now: NodeHasNoDiskPressure
# 84m         Normal    NodeAllocatableEnforced        node/minikube                       Updated Node Allocatable limit across pods
# 84m         Normal    Starting                       node/minikube                       Starting kubelet.
# 84m         Normal    NodeHasSufficientMemory        node/minikube                       Node minikube status is now: NodeHasSufficientMemory
# 84m         Normal    NodeHasSufficientPID           node/minikube                       Node minikube status is now: NodeHasSufficientPID
# 84m         Normal    Starting                       node/minikube                       
# ....
# 84m         Warning   BackOff                        pod/nginx-645dd4c5b5-hwch6          Back-off restarting failed container
```

This is a short slice of output, but as you can see, it shows you info but also a warning about `pod/nginx`.

As this command is very useful, you can also see information per resource type, this includes the manifest configuration and the most recent 10 events.  
For example:

```bash
kubectl describe deployment php                                                                                                                        Sun 30 Oct 2022 01:24:59 PM CET
# Name:                   php
# Namespace:              default
# CreationTimestamp:      Sun, 02 Oct 2022 12:13:15 +0200
# Labels:                 app=php
#                         app.kubernetes.io/managed-by=Helm
#                         project=workshop
# Annotations:            deployment.kubernetes.io/revision: 3
#                         meta.helm.sh/release-name: workshop
#                         meta.helm.sh/release-namespace: default
# Selector:               app=php
# Replicas:               1 desired | 1 updated | 1 total | 1 available | 0 unavailable
# StrategyType:           RollingUpdate
# MinReadySeconds:        0
# RollingUpdateStrategy:  25% max unavailable, 25% max surge
# Pod Template:
#   Labels:  app=php
#            project=workshop
#   Containers:
#    php:
#     Image:        workshop-php
#     Port:         9000/TCP
#     Host Port:    0/TCP
#     Environment:  <none>
#     Mounts:       <none>
#   Volumes:        <none>
# Conditions:
#   Type           Status  Reason
#   ----           ------  ------
#   Progressing    True    NewReplicaSetAvailable
#   Available      True    MinimumReplicasAvailable
# OldReplicaSets:  <none>
# NewReplicaSet:   php-bff6cbff8 (1/1 replicas created)
# Events:
#   Type    Reason             Age   From                   Message
#   ----    ------             ----  ----                   -------
#   Normal  ScalingReplicaSet  15m   deployment-controller  Scaled up replica set php-bff6cbff8 to 5 from 1
#   Normal  ScalingReplicaSet  8m8s  deployment-controller  Scaled down replica set php-bff6cbff8 to 1 from 5
```

Try it out for yourself on other resources like services/pods/cronjobs/ingresses etc.

# Exercises

Up till this point the workshop was about showing you around how stuff is done.  
From here on I will not provide copy/paste-able code anymore but leave it up to you.

## Update a container with Helm

In this exercise we will make a change to the codebase and re-deploy this to the cluster.  
The steps are as following:

- Find the correct PHP file in this repository
- Make a change, the title for example
- Rebuild and tag the Docker image **with a new tag or, it won't update**
- Update the cluster using Helm
- Refresh the page to verify your change is applied

Good luck! 😎

## Update a container without helm

Before we changed something, created a new docker image, with a new tag. When you are testing this can be a tedious task.  
In order to significantly speed up this process there is a trick we can do.  

Try and figure out how to apply a source code change without using helm.  
The steps are as following:

- Find the correct PHP file in this repository
- Make a change, the title for example
- ```do some trick here```
- Rebuild and tag the Docker image **may be the same tag**
- ```another trick here``` to make kubernetes _respawn_ the container

Good luck! 😎

## Run a command inside the container

Sometimes you need to run a command manually or, you just want to investigate what is going on within a container.
The steps are as following:

- Add a message first via the browser in our guestbook
- Verify the message is there
- Open a shell inside the PHP container
- Run the `clear.cronjob.php` script manually
- Refresh the browser to verify the message(s) are gone

Good luck! 😎

## Automate the command with a CronJob

CronJobs are a common pattern used in many applications. Kubernetes provides excellent support for this.   
In this exercise you will add one that automatically deletes all messages periodically.  

The steps are as following:

- Extend the helm chart in order to produce a cronjob resource
- Update your helm installation inside the cluster
- Add a message, wait 1 minute, refresh the page and verify it is gone
- Suspend the cronjob
- Add a message, wait 1 minute, refresh the page and verify it is not deleted

Good luck! 😎  
~~in exercise-answers is the fully worked out cronjob, but try to figure it out yourself first~~

## Scaling

In our minikube we will be using manual scaling for now.  
The reason for this is, because minikube doesn't have excellent support for the metrics server and can be very buggy.  

But the concept is still the same, except that we manually have to control the deployment.spec.replicas instead of letting the HPA do this for us.  
The steps are quite simple:

- Install apache benchmark
- Do a first run: ```ab -n100 -c5 'http://workshop.test/index.php?benchmark=1'```
- Note down how long this took
- Update the helm chart to increase the php deployment to 5 replicas
- Apply the update to the cluster
- Verify the number of pods have been increased
- Re-run the benchmark

Good luck! 😎

## Logging

Now we have more pods running of the same service it becomes harder to keep track of their output.  
We could of course open 5 terminals to tail all 5 php pods. This obviously becomes very cumbersome very soon.

The steps are:

- Figure out how to tail all php pods
- Figure out how to tail all pods for the workshop project (db + apache + php) at the same time
- Verify that it works by refreshing the page or maybe re-run the benchmark.

Good luck! 😎

# Conclusion

Thanks for taking part in this workshop. I hope you enjoyed.  
To summarise what we have covered:

- Building Docker containers
- Tagging containers
- Setting up a kubernetes cluster with minikube
- Learn to investigate resources and events that might help you identify problems
- Updating images within a cluster with Helm and the fast way without Helm
- Hooking into the Docker daemon of minikube instead of your local docker daemon
- Bash-ing into running containers for local script execution and eventually troubleshooting
- Inspecting/tailing pods
- Very basic scaling concepts
- CronJobs

Any questions or requests to dig in deeper, ask!
