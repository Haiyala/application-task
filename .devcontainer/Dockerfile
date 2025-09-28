# Base Ubuntu image
FROM mcr.microsoft.com/vscode/devcontainers/base:ubuntu

# Install PHP + required extensions
RUN apt-get update && \
    apt-get install -y php php-cli php-curl php-mbstring php-xml git curl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /workspaces/gondwana-rates
