# Sulu Helm Kubernetes

This repository contains an example configuration to deploy sulu to a kubernetes cluster.

The following instructions will deploy sulu to your local kubernetes cluster

## Build DOCKER image

```
cd sulu-minimal && docker build . -t sulu:latest
```

## Install HELM

```
brew install kubernetes-helm
```

## Initialize HELM and install TILLER

```
helm init
```

## Deploy SULU to cluster

```
helm install sulu-chart/ --name sulu
```

## Connect to pod and initialize database

```
kubectl get pods
```

Copy name of pod.

```
kubectl exec -it $POD_NAME -- /bin/bash
bin/console s:b dev
rm -rf var/cache var/logs
```

Open browser:

```
open http://127.0.0.1
```

TODO describe configuration and upgrade process
