# Use Ubuntu base image
FROM mcr.microsoft.com/vscode/devcontainers/base:ubuntu

# Install PHP and extensions
RUN apt-get update && \
    apt-get install -y php php-cli php-curl php-mbstring php-xml unzip git curl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Set workdir
WORKDIR /workspaces/gondwana-rate
