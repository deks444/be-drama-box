pipeline {
    agent any

    stages {
        stage('Preparation') {
            steps {
                // 1. Bersihkan workspace dari residu build sebelumnya
                cleanWs()
                
                // 2. Ambil kode dari repository
                checkout scm
            }
        }

        stage('Environment Fix') {
            steps {
                // Gunakan id kredensial yang tepat: dramabox-auth-env
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECURE_ENV_PATH')]) {
                    script {
                        // 1. Ambil path absolut workspace saat ini
                        def workspace = pwd()
                        
                        // 2. Gunakan single quotes agar aman dari Groovy Interpolation
                        // Kita paksa copy ke path absolut workspace
                        sh "cp -f '${SECURE_ENV_PATH}' ${workspace}/.env"
                        
                        // 3. Verifikasi keberadaan file
                        sh "ls -la ${workspace}/.env"
                    }
                }
            }
        }

        stage('Install & Build') {
            steps {
                // 4. Jalankan perintah Laravel standar
                sh 'composer install --no-interaction --prefer-dist'
                sh 'php artisan key:generate --force'
                sh 'npm install && npm run build'
            }
        }

        stage('Run Tests') {
            steps {
                // 5. Verifikasi fitur Auth Drama Box
                sh 'php artisan test'
            }
        }
    }

    post {
        always {
            // Menghapus file .env setelah build selesai agar tidak tertinggal di server Jenkins
            sh 'rm -f .env'
        }
        success {
            echo "✅ Build Drama-Box-Auth Sukses!"
        }
        failure {
            echo "❌ Build Gagal. Periksa log di atas."
        }
    }
}
