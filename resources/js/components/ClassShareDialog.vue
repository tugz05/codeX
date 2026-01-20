<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Copy, Share2, QrCode, Link as LinkIcon } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import QRCode from 'qrcode';

interface Props {
  open: boolean;
  classId: string;
  className: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const inviteLink = computed(() => {
  return `${window.location.origin}/student/classlist?code=${props.classId}`;
});

const hasWebShare = computed(() => {
  if (typeof window === 'undefined' || typeof window.navigator === 'undefined') return false;
  return typeof window.navigator.share === 'function';
});

const qrCodeDataUrl = ref<string>('');

const generateQRCode = async () => {
  try {
    const dataUrl = await QRCode.toDataURL(inviteLink.value, {
      width: 256,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF',
      },
    });
    qrCodeDataUrl.value = dataUrl;
  } catch (error) {
    console.error('Failed to generate QR code:', error);
    toast.error('Failed to generate QR code');
  }
};

const copyLink = () => {
  navigator.clipboard.writeText(inviteLink.value).then(() => {
    toast.success('Invite link copied to clipboard!');
  });
};

const copyClassCode = () => {
  navigator.clipboard.writeText(props.classId).then(() => {
    toast.success('Class code copied to clipboard!');
  });
};

const downloadQRCode = () => {
  if (!qrCodeDataUrl.value) return;
  
  const link = document.createElement('a');
  link.download = `codex-class-${props.classId}-qr.png`;
  link.href = qrCodeDataUrl.value;
  link.click();
};

const shareViaWebAPI = async () => {
  if (!hasWebShare.value || typeof window === 'undefined' || !window.navigator.share) {
    copyLink();
    return;
  }

  try {
    await window.navigator.share({
      title: `Join ${props.className} on codeX`,
      text: `Join my class "${props.className}" on codeX`,
      url: inviteLink.value,
    });
    toast.success('Shared successfully!');
  } catch (error: any) {
    if (error?.name !== 'AbortError') {
      toast.error('Failed to share');
    }
  }
};

watch(isOpen, (newVal) => {
  if (newVal) {
    generateQRCode();
  }
});

onMounted(() => {
  if (isOpen.value) {
    generateQRCode();
  }
});
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Share2 class="h-5 w-5" />
          Share Class: {{ className }}
        </DialogTitle>
        <DialogDescription>
          Share this class with your students using the link, class code, or QR code
        </DialogDescription>
      </DialogHeader>

      <Tabs default-value="link" class="w-full">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="link">
            <LinkIcon class="h-4 w-4 mr-2" />
            Link
          </TabsTrigger>
          <TabsTrigger value="code">
            <QrCode class="h-4 w-4 mr-2" />
            Class Code
          </TabsTrigger>
          <TabsTrigger value="qr">
            <QrCode class="h-4 w-4 mr-2" />
            QR Code
          </TabsTrigger>
        </TabsList>

        <!-- Link Tab -->
        <TabsContent value="link" class="space-y-4 mt-4">
          <div class="space-y-2">
            <Label for="invite-link-input">Invite Link</Label>
            <div class="flex gap-2">
              <Input
                id="invite-link-input"
                :model-value="inviteLink"
                readonly
                class="flex-1 font-mono text-sm"
              />
              <Button @click="copyLink" variant="outline" size="icon">
                <Copy class="h-4 w-4" />
              </Button>
              <Button
                v-if="hasWebShare"
                @click="shareViaWebAPI"
                variant="outline"
                size="icon"
                title="Share via native share dialog"
              >
                <Share2 class="h-4 w-4" />
              </Button>
            </div>
            <p class="text-xs text-muted-foreground">
              Students can click this link to join your class
            </p>
          </div>
        </TabsContent>

        <!-- Class Code Tab -->
        <TabsContent value="code" class="space-y-4 mt-4">
          <div class="space-y-3">
            <Label for="class-code-input" class="text-base font-semibold">Class Code</Label>
            <div class="flex gap-2">
              <Input
                id="class-code-input"
                :model-value="classId"
                readonly
                class="flex-1 font-mono text-xl font-bold text-center tracking-[0.2em] bg-muted/50 border-2 py-3"
              />
              <Button @click="copyClassCode" variant="outline" size="icon" class="h-auto">
                <Copy class="h-4 w-4" />
              </Button>
            </div>
            <p class="text-xs text-muted-foreground">
              Students can enter this code on the join class page or use the invite link above
            </p>
          </div>
        </TabsContent>

        <!-- QR Code Tab -->
        <TabsContent value="qr" class="space-y-4 mt-4">
          <div class="space-y-4">
            <div class="flex flex-col items-center space-y-4">
              <div
                v-if="qrCodeDataUrl"
                class="p-4 bg-white rounded-lg border-2 border-border shadow-sm"
              >
                <img
                  :src="qrCodeDataUrl"
                  alt="QR Code for class invite"
                  class="w-64 h-64"
                />
              </div>
              <div v-else class="w-64 h-64 flex items-center justify-center bg-muted rounded-lg">
                <p class="text-sm text-muted-foreground">Generating QR code...</p>
              </div>
              <div class="flex gap-2">
                <Button @click="downloadQRCode" variant="outline" :disabled="!qrCodeDataUrl">
                  <QrCode class="h-4 w-4 mr-2" />
                  Download QR Code
                </Button>
                <Button @click="copyLink" variant="outline">
                  <Copy class="h-4 w-4 mr-2" />
                  Copy Link
                </Button>
              </div>
            </div>
            <p class="text-xs text-muted-foreground text-center">
              Students can scan this QR code with their phone to join your class
            </p>
          </div>
        </TabsContent>
      </Tabs>
    </DialogContent>
  </Dialog>
</template>
