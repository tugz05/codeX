#!/bin/bash
# Setup script for code runner on Linux/Unix systems
# This script installs required dependencies for Python, C++, and Java

set -e

echo "=== Code Runner Setup Script ==="
echo ""

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
else
    echo "Unsupported OS: $OSTYPE"
    exit 1
fi

echo "Detected OS: $OS"
echo ""

# Check if running as root (for system-wide install)
if [ "$EUID" -eq 0 ]; then
    INSTALL_MODE="system"
else
    INSTALL_MODE="user"
    echo "Note: Running as non-root. Some installations may require sudo."
    echo ""
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to install on Linux
install_linux() {
    echo "Installing dependencies for Linux..."
    
    if command_exists apt-get; then
        echo "Using apt-get (Debian/Ubuntu)"
        sudo apt-get update
        sudo apt-get install -y \
            python3 \
            build-essential \
            default-jdk \
            nodejs \
            npm
    elif command_exists yum; then
        echo "Using yum (CentOS/RHEL)"
        sudo yum install -y \
            python3 \
            gcc-c++ \
            java-11-openjdk-devel \
            nodejs \
            npm
    elif command_exists dnf; then
        echo "Using dnf (Fedora)"
        sudo dnf install -y \
            python3 \
            gcc-c++ \
            java-11-openjdk-devel \
            nodejs \
            npm
    else
        echo "Package manager not detected. Please install manually:"
        echo "  - Python 3"
        echo "  - G++ (build-essential)"
        echo "  - JDK (Java Development Kit)"
        echo "  - Node.js and npm"
        exit 1
    fi
}

# Function to install on macOS
install_macos() {
    echo "Installing dependencies for macOS..."
    
    if ! command_exists brew; then
        echo "Homebrew not found. Installing Homebrew..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    fi
    
    echo "Installing packages via Homebrew..."
    brew install python3
    brew install gcc
    brew install openjdk
    brew install node
    
    # Xcode Command Line Tools (for g++)
    if ! xcode-select -p &>/dev/null; then
        echo "Installing Xcode Command Line Tools..."
        xcode-select --install
    fi
}

# Install dependencies based on OS
if [ "$OS" == "linux" ]; then
    install_linux
elif [ "$OS" == "macos" ]; then
    install_macos
fi

echo ""
echo "=== Verifying installations ==="

# Check Python
if command_exists python3; then
    PYTHON_VERSION=$(python3 --version)
    echo "✓ Python: $PYTHON_VERSION"
else
    echo "✗ Python 3 not found"
fi

# Check G++
if command_exists g++; then
    GPP_VERSION=$(g++ --version | head -n1)
    echo "✓ G++: $GPP_VERSION"
else
    echo "✗ G++ not found"
fi

# Check Java
if command_exists javac; then
    JAVA_VERSION=$(javac -version 2>&1)
    echo "✓ Java Compiler: $JAVA_VERSION"
else
    echo "✗ Java Compiler (javac) not found"
fi

if command_exists java; then
    JAVA_RUNTIME=$(java -version 2>&1 | head -n1)
    echo "✓ Java Runtime: $JAVA_RUNTIME"
else
    echo "✗ Java Runtime not found"
fi

# Check Node.js
if command_exists node; then
    NODE_VERSION=$(node --version)
    echo "✓ Node.js: $NODE_VERSION"
else
    echo "✗ Node.js not found"
fi

# Check npm
if command_exists npm; then
    NPM_VERSION=$(npm --version)
    echo "✓ npm: $NPM_VERSION"
else
    echo "✗ npm not found"
fi

echo ""
echo "=== Installing Node.js dependencies ==="
cd "$(dirname "$0")"
npm install

echo ""
echo "=== Setup complete! ==="
echo ""
echo "To start the runner server:"
echo "  npm start"
echo ""
echo "Or with PM2 (recommended for production):"
echo "  pm2 start server.js --name code-runner"
echo ""
